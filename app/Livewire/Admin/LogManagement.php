<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class LogManagement extends Component
{
    use AuthorizesRequests, Toast, WithPagination;

    public array $logs = [];
    public bool $showModal = false;
    public int $logIndexToDelete = -1;
    public string $searchLevel = '';
    public string $searchText = '';

    public array $levelOptions = [
        ['id' => '', 'name' => 'All Levels'],
        ['id' => 'emergency', 'name' => 'Emergency'],
        ['id' => 'alert', 'name' => 'Alert'],
        ['id' => 'critical', 'name' => 'Critical'],
        ['id' => 'error', 'name' => 'Error'],
        ['id' => 'warning', 'name' => 'Warning'],
        ['id' => 'notice', 'name' => 'Notice'],
        ['id' => 'info', 'name' => 'Info'],
        ['id' => 'debug', 'name' => 'Debug'],
    ];

    public function mount(): void
    {
        // Only superadmin can access log management
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Unauthorized access.');
        $this->loadLogs();
    }

    public function render(): View
    {
        $filteredLogs = $this->getFilteredLogs();

        return view('livewire.admin.log-management', [
            'logs' => $filteredLogs,
            'logInfo' => $this->getLogFileInfo(),
        ]);
    }

    /**
     * Load and parse Laravel log file
     */
    public function loadLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            $this->logs = [];
            return;
        }

        $content = File::get($logPath);
        $this->logs = $this->parseLogFile($content);
    }

    /**
     * Parse log file content into structured array
     */
    protected function parseLogFile(string $content): array
    {
        $logs = [];
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+?)(?=\[\d{4}-\d{2}-\d{2}|$)/s';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $index => $match) {
            $logs[] = [
                'index' => $index,
                'datetime' => $match[1] ?? '',
                'environment' => $match[2] ?? '',
                'level' => strtolower($match[3] ?? ''),
                'message' => trim($match[4] ?? ''),
                'raw' => $match[0] ?? '',
            ];
        }

        // Reverse to show newest first
        return array_reverse($logs);
    }

    /**
     * Get filtered logs based on search criteria
     */
    protected function getFilteredLogs(): array
    {
        $filtered = $this->logs;

        // Filter by level
        if (!empty($this->searchLevel)) {
            $filtered = array_filter($filtered, function ($log) {
                return strtolower($log['level']) === strtolower($this->searchLevel);
            });
        }

        // Filter by text search
        if (!empty($this->searchText)) {
            $searchLower = strtolower($this->searchText);
            $filtered = array_filter($filtered, function ($log) use ($searchLower) {
                return str_contains(strtolower($log['message']), $searchLower) ||
                    str_contains(strtolower($log['level']), $searchLower) ||
                    str_contains(strtolower($log['datetime']), $searchLower);
            });
        }

        return array_values($filtered);
    }

    /**
     * Get log file information
     */
    protected function getLogFileInfo(): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            return [
                'exists' => false,
                'size' => 0,
                'size_formatted' => '0 B',
                'modified' => null,
                'total_entries' => 0,
            ];
        }

        $size = File::size($logPath);

        return [
            'exists' => true,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'modified' => date('Y-m-d H:i:s', File::lastModified($logPath)),
            'total_entries' => count($this->logs),
        ];
    }

    /**
     * Confirm log entry deletion
     */
    public function confirmDelete(int $logIndex): void
    {
        $this->logIndexToDelete = $logIndex;
        $this->showModal = true;
    }

    /**
     * Delete a specific log entry from the file
     */
    public function deleteLogEntry(): void
    {
        if ($this->logIndexToDelete === -1) {
            $this->error('No log entry selected for deletion.');
            return;
        }

        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            $this->error('Log file not found!');
            $this->showModal = false;
            return;
        }

        try {
            // Find the log entry to delete
            $logToDelete = null;
            foreach ($this->logs as $log) {
                if ($log['index'] === $this->logIndexToDelete) {
                    $logToDelete = $log;
                    break;
                }
            }

            if (!$logToDelete) {
                $this->error('Log entry not found!');
                $this->showModal = false;
                return;
            }

            // Read current content
            $content = File::get($logPath);

            // Remove the specific log entry
            $content = str_replace($logToDelete['raw'], '', $content);

            // Write back to file
            File::put($logPath, $content);

            // Reload logs
            $this->loadLogs();

            $this->success('Log entry deleted successfully!');

            // Log the deletion activity
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'log_level' => $logToDelete['level'],
                    'log_datetime' => $logToDelete['datetime'],
                    'log_message_preview' => substr($logToDelete['message'], 0, 100),
                ])
                ->log('Deleted log entry');
        } catch (\Exception $e) {
            $this->error('Failed to delete log entry: ' . $e->getMessage());
        }

        $this->showModal = false;
        $this->logIndexToDelete = -1;
    }

    /**
     * Download laravel.log file
     */
    public function downloadLog(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            $this->error('Log file not found!');
            return response()->download($logPath);
        }

        // Log the download activity
        activity()
            ->causedBy(auth()->user())
            ->withProperties(['log_file' => 'laravel.log'])
            ->log('Downloaded log file');

        return response()->download($logPath);
    }

    /**
     * Clear entire log file
     */
    public function clearAllLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            $this->warning('Log file does not exist.');
            return;
        }

        try {
            File::put($logPath, '');
            $this->logs = [];

            $this->success('All logs cleared successfully!');

            // Log the activity
            activity()
                ->causedBy(auth()->user())
                ->log('Cleared all log entries from laravel.log');
        } catch (\Exception $e) {
            $this->error('Failed to clear logs: ' . $e->getMessage());
        }
    }

    /**
     * Reload logs
     */
    public function refreshLogs(): void
    {
        $this->loadLogs();
        $this->success('Logs refreshed successfully!');
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get badge class for log level
     */
    public function getLevelBadgeClass(string $level): string
    {
        return match (strtolower($level)) {
            'emergency', 'alert', 'critical', 'error' => 'badge-error',
            'warning' => 'badge-warning',
            'notice' => 'badge-info',
            'info' => 'badge-success',
            'debug' => 'badge-ghost',
            default => 'badge-neutral',
        };
    }

    /**
     * Close delete confirmation modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->logIndexToDelete = -1;
    }

    /**
     * Updated search filters
     */
    public function updatedSearchLevel(): void
    {
        $this->resetPage();
    }

    public function updatedSearchText(): void
    {
        $this->resetPage();
    }
}

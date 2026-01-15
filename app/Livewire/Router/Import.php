<?php

namespace App\Livewire\Router;

use App\Models\Router;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class Import extends Component
{
    use AuthorizesRequests, Toast, WithFileUploads;



    public string $selectedTab = 'mikhmon';

    protected $queryString = ['selectedTab' => ['except' => 'mikhmon']];

    public function mount(): void
    {
        $this->authorize('import_router_configs');
    }

    // Mikhmon config file - Only plain text, NO executable files
    // Allow various MIME types that text files with PHP content might have
    #[Rule(['nullable', 'file', 'max:2048'])]
    public $configFile;

    // CSV file - Only CSV/text, validate content type
    #[Rule(['nullable', 'file', 'max:2048', 'mimes:csv,txt'])]
    public $csvFile;

    public bool $parsedReady = false;

    public bool $skipExisting = true;

    public array $parsed = [];

    public bool $csvParsedReady = false;

    public array $csvParsed = [];

    public function updatedConfigFile(): void
    {
        $this->reset('parsed', 'parsedReady');

        try {
            $this->validateOnly('configFile');
        } catch (\Exception $e) {
            $this->addError('configFile', $e->getMessage());
            return;
        }

        // Strict file extension validation
        $ext = strtolower($this->configFile->getClientOriginalExtension());

        if ($ext !== 'txt') {
            $this->addError('configFile', 'Only .txt files are allowed for security reasons.');
            return;
        }

        // Validate MIME type from actual file content - allow common text MIME types
        $mimeType = $this->configFile->getMimeType();

        $allowedMimes = [
            'text/plain',
            'text/x-php',
            'application/x-php',
            'text/html',
            'application/octet-stream',
            'text/x-c',
            'text/x-c++'
        ];
        if (!in_array($mimeType, $allowedMimes)) {
            $this->addError('configFile', 'Invalid file type detected. Please ensure it\'s a plain text file.');
            return;
        }

        // Size check before reading
        if ($this->configFile->getSize() > 2097152) { // 2MB
            $this->addError('configFile', 'File is too large. Maximum size is 2MB.');
            return;
        }

        $contents = file_get_contents($this->configFile->getRealPath());

        // Ensure file is not empty
        if (empty(trim($contents))) {
            $this->addError('configFile', 'File is empty.');
            return;
        }

        try {
            $this->parsed = $this->parseMikhmonConfig($contents);
        } catch (\Exception $e) {
            $this->addError('configFile', 'Error parsing file: ' . $e->getMessage());
            return;
        }

        if (empty($this->parsed)) {
            $this->addError('configFile', 'No valid router configurations found in the file. Please check the file format.');
            return;
        }

        $user = Auth::user();
        $newRoutersToCreate = $this->calculateNewRoutersCount();

        if (!$user->canAddRouters($newRoutersToCreate)) {
            $availableSlots = $user->getRemainingRouterSlots();
            $this->warning(
                title: 'Router Limit Warning',
                description: "This file contains {$newRoutersToCreate} new routers, but you only have {$availableSlots} available slots. Import will fail unless you upgrade your subscription."
            );
        }

        $this->parsedReady = true;
    }

    public function import(): void
    {
        $this->authorize('import_router_configs');

        // No need to validate here - validation happens in updatedConfigFile()
        // $this->validate() was causing "csv file is required" error when importing Mikhmon

        if (empty($this->parsed)) {
            $this->addError('configFile', 'Please select a valid config file first.');

            return;
        }

        $user = Auth::user();
        $newRoutersToCreate = $this->calculateNewRoutersCount();

        if (!$user->canAddRouters($newRoutersToCreate)) {
            $package = $user->getCurrentPackage();
            $availableSlots = $user->getRemainingRouterSlots();

            if (!$user->hasActiveSubscription()) {
                $this->error(
                    title: 'No Active Subscription',
                    description: 'You need an active subscription to import routers.',
                    redirectTo: route('subscription.index')
                );
            } else {
                $this->error(
                    title: 'Router Limit Exceeded',
                    description: "You are trying to import {$newRoutersToCreate} routers, but you only have {$availableSlots} available slots out of {$package->max_routers} allowed by your {$package->name} package. Please upgrade your subscription or reduce the number of routers to import.",
                    redirectTo: route('subscription.index')
                );
            }
            return;
        }

        $created = 0;
        $skipped = 0;
        $user = Auth::user();
        $hitLimit = false;

        foreach ($this->parsed as $item) {
            $exists = Router::query()
                ->where('address', $item['address'])
                ->where('port', $item['port'])
                ->exists();

            if ($exists && $this->skipExisting) {
                $skipped++;
                continue;
            }

            // If router doesn't exist, it's a NEW router - check limit BEFORE creating
            if (!$exists) {
                // Check if user can add this router
                if (!$user->canAddRouters(1)) {
                    $package = $user->getCurrentPackage();
                    $availableSlots = $user->getRemainingRouterSlots();
                    $this->error(
                        title: 'Router Limit Exceeded',
                        description: "Import stopped: You have reached your limit. Created {$created} routers. You have {$availableSlots} available slots out of {$package->max_routers} allowed by your {$package->name} package."
                    );
                    $hitLimit = true;
                    break; // Stop importing more routers
                }
            }

            try {
                // Password comes base64-encoded from parseMikhmonConfig (plain text that was base64 encoded)
                // Decode it back to plain text, then encrypt with Laravel's Crypt for database storage
                $plainPassword = base64_decode($item['password']);

                // Create or update the router with Laravel-encrypted password
                $router = Router::updateOrCreate(
                    ['address' => $item['address'], 'port' => (int) $item['port']],
                    [
                        'name' => $item['name'],
                        'username' => $item['username'],
                        'password' => Crypt::encryptString($plainPassword),
                        'login_address' => $item['login_address'] ?? null,
                        'note' => $item['ssid'] ?? null,
                        'app_key' => bin2hex(random_bytes(16)),
                        'user_id' => Auth::id(),
                    ]
                );

                $created++;
            } catch (\Exception $e) {
                // Silently continue on error
            }
        }

        // Only show success and reset if we didn't hit the limit
        if (!$hitLimit) {
            $this->dispatch(
                'notify',
                type: 'success',
                message: "Import successful: created/updated {$created}, skipped {$skipped}."
            );

            $this->reset(['configFile', 'parsed', 'parsedReady']);
            $this->skipExisting = true;
        }

        return;
    }

    protected function calculateNewRoutersCount(): int
    {
        $newRoutersToCreate = 0;

        foreach ($this->parsed as $item) {
            $exists = Router::query()
                ->where('address', $item['address'])
                ->where('port', $item['port'])
                ->exists();

            // Only count as "new" if it doesn't exist
            if (!$exists) {
                $newRoutersToCreate++;
            }
        }

        return $newRoutersToCreate;
    }

    protected function parseMikhmonConfig(string $contents): array
    {
        $routers = [];

        if (! preg_match_all(
            '/\$data\s*\[\'([^\']+)\'\]\s*=\s*array\s*\((.*?)\);/s',
            $contents,
            $matches,
            PREG_SET_ORDER
        )) {
            return $routers;
        }

        foreach ($matches as $block) {
            $key = $block[1];
            $body = $block[2];

            if (Str::lower($key) === 'mikhmon') {
                continue;
            }

            if (! preg_match_all("/'([^']*)'/", $body, $lines)) {

                continue;
            }
            $values = $lines[1];

            $name = $key;
            $host = null;
            $port = null;
            $username = null;
            $passwordRaw = null;
            $ssid = null;
            $loginAddress = null;

            foreach ($values as $v) {
                if (str_contains($v, '!') && str_contains($v, ':') && $host === null) {
                    [$left, $right] = explode('!', $v, 2);
                    $maybeName = trim($left);
                    if ($maybeName !== '') {
                        $name = $maybeName;
                    }
                    if (str_contains($right, ':')) {
                        [$h, $p] = explode(':', $right, 2);
                        $host = trim($h);
                        $port = (int) trim($p);
                    }

                    continue;
                }

                if (str_contains($v, '@|@') && $username === null) {
                    [, $username] = explode('@|@', $v, 2);
                    $username = trim($username);

                    continue;
                }

                if (str_contains($v, '#|#') && $passwordRaw === null) {
                    [, $pwd] = explode('#|#', $v, 2);
                    $passwordRaw = trim($pwd);

                    continue;
                }

                if (str_contains($v, '%')) {
                    [, $ssid] = explode('%', $v, 2);
                    $ssid = trim($ssid);
                    if ($ssid !== '') {
                        $ssid = $ssid;
                    }
                    continue;
                }

                if (str_contains($v, '^')) {
                    [, $domain] = explode('^', $v, 2);
                    $domain = trim($domain);
                    if ($domain !== '') {
                        $loginAddress = $domain;
                    }

                    continue;
                }
            }

            if ($host && $port && $username && $passwordRaw) {
                // Validate address - support IP addresses and domain names with subdomains
                $isValidIp = filter_var($host, FILTER_VALIDATE_IP) !== false;

                // More flexible domain validation that allows subdomains
                // Allows: example.com, sub.example.com, server2.remotemikrotik.com, etc.
                $isValidDomain = preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)+$/', $host) === 1;

                if (!$isValidIp && !$isValidDomain) {
                    continue; // Skip invalid host
                }

                // Validate port range
                if ($port < 1 || $port > 65535) {
                    continue;
                }

                // Decrypt Mikhmon password using their algorithm, then base64 encode for safe transport
                // Flow: Mikhmon encrypted -> decryptPassword() -> plain text -> base64_encode() -> safe storage
                $decryptedPassword = $this->decryptPassword($passwordRaw);
                $safePassword = base64_encode($decryptedPassword); // Encoded plain text for safe JSON transport

                $routers[] = [
                    'name' => $this->sanitizeInput($name),
                    'address' => $host,
                    'port' => (int) $port,
                    'username' => $this->sanitizeInput($username),
                    'password' => $safePassword, // Base64 encoded decrypted password
                    'login_address' => $loginAddress ? $this->sanitizeInput($loginAddress) : null,
                    'ssid' => $ssid ? $this->sanitizeInput($ssid) : null,
                ];
            }
        }

        return $routers;
    }

    /**
     * Sanitize input to prevent CSV injection, XSS, and other attacks
     */
    protected function sanitizeInput(string $value): string
    {
        // Trim whitespace
        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        // Prevent CSV injection attacks (formulas starting with =, +, -, @, |, %)
        // When exported to spreadsheets, these can execute malicious code
        if (preg_match('/^[=+\-@|%]/', $value)) {
            $value = "'" . $value; // Prefix with single quote to neutralize
        }

        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Strip HTML/PHP tags
        $value = strip_tags($value);

        // Remove control characters except newlines and tabs
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        // Limit length to prevent buffer overflow issues
        $value = mb_substr($value, 0, 255);

        return $value;
    }

    /**
     * Decrypt Mikhmon password using exact same algorithm as Mikhmon
     * Matches: function decrypt($string, $key=128) from Mikhmon
     */
    public function decryptPassword($string, $key = 128): string
    {
        $result = '';
        $key = (string) $key; // Convert key to string (128 becomes "128")

        // Base64 decode the encrypted string
        $decoded = base64_decode($string, true);

        if ($decoded === false) {
            return ''; // Invalid base64
        }

        $keyLen = strlen($key);
        if ($keyLen === 0) {
            return $decoded;
        }

        // Decrypt character by character - EXACT Mikhmon logic
        for ($i = 0, $k = strlen($decoded); $i < $k; $i++) {
            $char = $decoded[$i];

            // Get key character using Mikhmon's exact formula: ($i % strlen($key))-1
            $keyIndex = ($i % $keyLen) - 1;
            $keychar = substr($key, $keyIndex, 1);

            // Subtract key character's ASCII value from encrypted character
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }

        return $result;
    }

    public function importMikhmon()
    {
        $this->import();

        // Only redirect if import was successful (no errors)
        if (!$this->getErrorBag()->any()) {
            $this->redirect(route('routers.index'), navigate: true);
        }
    }

    public function updatedCsvFile(): void
    {
        $this->reset('csvParsed', 'csvParsedReady');
        $this->validateOnly('csvFile');

        // Strict file extension validation
        $ext = strtolower($this->csvFile->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'])) {
            $this->addError('csvFile', 'Only .csv or .txt files are allowed.');
            return;
        }

        // Validate MIME type from actual file content
        $mimeType = $this->csvFile->getMimeType();
        $allowedMimes = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream'];
        if (!in_array($mimeType, $allowedMimes)) {
            $this->addError('csvFile', 'Invalid file type detected.');
            return;
        }

        // Size check
        if ($this->csvFile->getSize() > 2097152) { // 2MB
            $this->addError('csvFile', 'File is too large. Maximum size is 2MB.');
            return;
        }

        try {
            $path = $this->csvFile->getRealPath();
            $this->csvParsed = $this->parseCsvFile($path);

            if (empty($this->csvParsed)) {
                $this->addError('csvFile', 'No valid router configurations found in the CSV file.');
                return;
            }

            $user = Auth::user();
            $newRoutersToCreate = $this->calculateNewRoutersCountFromCsv();

            if (!$user->canAddRouters($newRoutersToCreate)) {
                $availableSlots = $user->getRemainingRouterSlots();
                $this->warning(
                    title: 'Router Limit Warning',
                    description: "This CSV contains {$newRoutersToCreate} new routers, but you only have {$availableSlots} available slots. Import will fail unless you upgrade your subscription."
                );
            }

            $this->csvParsedReady = true;
        } catch (\Exception $e) {
            $this->addError('csvFile', 'Error reading CSV file: ' . $e->getMessage());
        }
    }

    protected function parseCsvFile(string $path, int $maxRows = 1000): array
    {
        $routers = [];
        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new \Exception('Unable to open CSV file.');
        }

        // Handle BOM (Byte Order Mark) if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle); // Not UTF-8 BOM, rewind
        }

        // Read header row with no length limit (0)
        $header = fgetcsv($handle, 0, ',');

        if (!$header || empty($header)) {
            fclose($handle);
            throw new \Exception('CSV file is empty or has invalid format.');
        }

        // Normalize header (lowercase and trim)
        $header = array_map(fn($col) => strtolower(trim($col)), $header);

        // Required columns
        $requiredColumns = ['name', 'address', 'port', 'username', 'password'];
        $missingColumns = array_diff($requiredColumns, $header);

        if (!empty($missingColumns)) {
            throw new \Exception('Missing required columns: ' . implode(', ', $missingColumns));
        }

        $lineNumber = 1;
        $parsedCount = 0;

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $lineNumber++;

            // Limit total rows to prevent resource exhaustion
            if ($parsedCount >= $maxRows) {
                fclose($handle);
                throw new \Exception("CSV file exceeds maximum allowed rows ({$maxRows}). Please split into smaller files.");
            }

            // Skip completely empty rows
            if (empty(array_filter($data, fn($v) => $v !== null && trim($v) !== ''))) {
                continue;
            }

            // Validate row has correct number of columns
            if (count($data) !== count($header)) {
                continue; // Skip malformed rows silently
            }

            // Create associative array
            $row = array_combine($header, $data);

            if ($row === false) {
                continue; // Skip if combine fails
            }

            // Validate required fields
            $name = trim($row['name'] ?? '');
            $address = trim($row['address'] ?? '');
            $port = trim($row['port'] ?? '');
            $username = trim($row['username'] ?? '');
            $password = trim($row['password'] ?? '');

            // Validate data
            if (empty($name) || empty($address) || empty($port) || empty($username) || empty($password)) {
                continue; // Skip invalid rows
            }

            // Strict IP/hostname validation
            $isValidIp = filter_var($address, FILTER_VALIDATE_IP) !== false;
            // Support subdomains and multi-level domains
            $isValidDomain = preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)+$/', $address) === 1;

            if (!$isValidIp && !$isValidDomain) {
                continue; // Skip invalid IP/hostname
            }

            // Validate port number
            if (!is_numeric($port) || (int)$port < 1 || (int)$port > 65535) {
                continue; // Skip invalid port
            }

            // Validate and sanitize login_address if present
            $loginAddress = null;
            if (isset($row['login_address'])) {
                $loginAddr = trim($row['login_address']);
                if ($loginAddr !== '') {
                    // Validate as IP, hostname (with subdomains), or URL
                    $isValidLoginIp = filter_var($loginAddr, FILTER_VALIDATE_IP) !== false;
                    $isValidLoginDomain = preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)+$/', $loginAddr) === 1;
                    $isValidLoginUrl = filter_var($loginAddr, FILTER_VALIDATE_URL) !== false;

                    if ($isValidLoginIp || $isValidLoginDomain || $isValidLoginUrl) {
                        $loginAddress = $this->sanitizeInput($loginAddr);
                    }
                }
            }

            // Sanitize all text inputs (prevent CSV injection, XSS, etc.)
            $routers[] = [
                'name' => $this->sanitizeInput($name),
                'address' => $address, // Already validated, no need to sanitize
                'port' => (int) $port,
                'username' => $this->sanitizeInput($username),
                'password' => $password, // Will be encrypted, but still sanitize
                'login_address' => $loginAddress,
                'note' => isset($row['note']) ? $this->sanitizeInput(trim($row['note'])) : null,
            ];

            $parsedCount++;
        }

        fclose($handle);
        return $routers;
    }

    protected function calculateNewRoutersCountFromCsv(): int
    {
        $newRoutersToCreate = 0;

        foreach ($this->csvParsed as $item) {
            $exists = Router::query()
                ->where('address', $item['address'])
                ->where('port', $item['port'])
                ->exists();

            if (!$exists) {
                $newRoutersToCreate++;
            }
        }

        return $newRoutersToCreate;
    }

    public function importCsv(): void
    {
        $this->authorize('import_router_configs');

        $this->validate([
            'csvFile' => 'required|file|max:4096|mimes:csv,txt',
        ]);

        if (empty($this->csvParsed)) {
            $this->addError('csvFile', 'Please select a valid CSV file first.');
            return;
        }

        $user = Auth::user();
        $newRoutersToCreate = $this->calculateNewRoutersCountFromCsv();

        if (!$user->canAddRouters($newRoutersToCreate)) {
            $package = $user->getCurrentPackage();
            $availableSlots = $user->getRemainingRouterSlots();

            if (!$user->hasActiveSubscription()) {
                $this->error(
                    title: 'No Active Subscription',
                    description: 'You need an active subscription to import routers.',
                    redirectTo: route('subscription.index')
                );
            } else {
                $this->error(
                    title: 'Router Limit Exceeded',
                    description: "You are trying to import {$newRoutersToCreate} routers, but you only have {$availableSlots} available slots out of {$package->max_routers} allowed by your {$package->name} package. Please upgrade your subscription or reduce the number of routers to import.",
                    redirectTo: route('subscription.index')
                );
            }
            return;
        }

        $created = 0;
        $skipped = 0;
        $hitLimit = false;

        foreach ($this->csvParsed as $item) {
            $exists = Router::query()
                ->where('address', $item['address'])
                ->where('port', $item['port'])
                ->exists();

            if ($exists && $this->skipExisting) {
                $skipped++;
                continue;
            }

            if (!$exists) {
                if (!$user->canAddRouters(1)) {
                    $package = $user->getCurrentPackage();
                    $availableSlots = $user->getRemainingRouterSlots();
                    $this->error(
                        title: 'Router Limit Exceeded',
                        description: "Import stopped: You have reached your limit. Created {$created} routers. You have {$availableSlots} available slots out of {$package->max_routers} allowed by your {$package->name} package."
                    );
                    $hitLimit = true;
                    break;
                }
            }

            Router::updateOrCreate(
                ['address' => $item['address'], 'port' => (int) $item['port']],
                [
                    'name' => $item['name'],
                    'username' => $item['username'],
                    'password' => Crypt::encryptString($item['password']),
                    'login_address' => $item['login_address'] ?? null,
                    'note' => $item['note'] ?? null,
                    'app_key' => bin2hex(random_bytes(16)),
                    'user_id' => Auth::id(),
                ]
            );

            $created++;
        }

        if (!$hitLimit) {
            $this->dispatch(
                'notify',
                type: 'success',
                message: "CSV Import successful: created/updated {$created}, skipped {$skipped}."
            );

            $this->reset(['csvFile', 'csvParsed', 'csvParsedReady']);
            $this->redirect(route('routers.index'), navigate: true);
        }
    }

    public function cancel()
    {
        $this->redirect(route('routers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.router.import');
    }
}

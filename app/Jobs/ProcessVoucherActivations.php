<?php

namespace App\Jobs;

use App\Models\Voucher;
use App\Services\VoucherLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessVoucherActivations implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $activations
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        Log::info('Processing voucher activations', [
            'count' => count($this->activations),
        ]);

        foreach ($this->activations as $activation) {
            try {
                $result = $this->processActivation($activation);
                
                if ($result === 'processed') {
                    $processedCount++;
                } elseif ($result === 'skipped') {
                    $skippedCount++;
                }
                
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to process activation', [
                    'activation' => $activation,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Voucher activation processing completed', [
            'processed' => $processedCount,
            'skipped' => $skippedCount,
            'errors' => $errorCount,
            'total' => count($this->activations),
        ]);
    }

    /**
     * Process a single activation
     * Only updates if activated_at or mac_address are empty
     * 
     * @return string 'processed', 'skipped', or 'error'
     */
    protected function processActivation(array $activation): string
    {
        $username = $activation['username'];
        $nasIdentifier = $activation['nas_identifier'] ?? null;
        $authenticatedAt = Carbon::parse($activation['authenticated_at']);
        $macAddress = $activation['calling_station_id'] ?? null;

        // Find voucher by username
        $voucherQuery = Voucher::where('username', $username);

        // If NAS identifier provided, filter by router
        if ($nasIdentifier) {
            $voucherQuery->whereHas('router', function ($query) use ($nasIdentifier) {
                $query->where('nas_identifier', $nasIdentifier)
                    ->orWhere('host', $nasIdentifier)
                    ->orWhere('ip', $nasIdentifier);
            });
        }

        $voucher = $voucherQuery->first();

        if (!$voucher) {
            Log::debug('Voucher not found for activation', [
                'username' => $username,
                'nas_identifier' => $nasIdentifier,
            ]);
            return 'skipped';
        }

        // Check if both activated_at AND mac_address are already set
        if ($voucher->activated_at && $voucher->mac_address) {
            Log::debug('Voucher already fully activated with MAC', [
                'username' => $username,
                'activated_at' => $voucher->activated_at,
                'mac_address' => $voucher->mac_address,
            ]);
            return 'skipped';
        }

        // Load profile relationship
        $profile = $voucher->profile;

        if (!$profile) {
            Log::error('Voucher has no profile', [
                'voucher_id' => $voucher->id,
                'username' => $username,
            ]);
            return 'error';
        }

        $updated = false;

        // Only set activated_at if empty
        if (!$voucher->activated_at) {
            $voucher->activated_at = $authenticatedAt;
            
            // Calculate expiry date based on profile validity
            $expiryDate = $authenticatedAt->copy()->addHours($profile->validity_hours);
            $voucher->expiry_date = $expiryDate;
            $voucher->status = 'active';
            
            $updated = true;
            
            Log::info('Voucher activation time set', [
                'username' => $username,
                'activated_at' => $authenticatedAt->toDateTimeString(),
                'expires_at' => $expiryDate->toDateTimeString(),
            ]);
        }

        // Only set mac_address if empty
        if (!$voucher->mac_address && $macAddress) {
            $voucher->mac_address = $macAddress;
            $updated = true;
            
            Log::info('Voucher MAC address bound', [
                'username' => $username,
                'mac_address' => $macAddress,
            ]);
        }

        if ($updated) {
            $voucher->save();
            
            // Log activation event
            VoucherLogger::log(
                $voucher,
                $voucher->router,
                'activated',
                [
                    'activated_at' => $voucher->activated_at?->toDateTimeString(),
                    'mac_address' => $voucher->mac_address,
                    'nas_identifier' => $nasIdentifier,
                    'expiry_date' => $voucher->expiry_date?->toDateTimeString(),
                    'status' => $voucher->status,
                    'batch' => $voucher->batch,
                ],
                'Voucher activated via RADIUS authentication'
            );
            
            Log::info('Voucher activation logged', [
                'username' => $username,
                'voucher_id' => $voucher->id,
            ]);
            
            return 'processed';
        }

        return 'skipped';
    }
}

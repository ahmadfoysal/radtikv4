<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// Process Facade টি Laravel 9+ এ exec() এর চেয়ে ভালো।
use Illuminate\Support\Facades\Process;

class DeployController extends Controller
{
    // Secret key should be stored in config/services.php as 'github.webhook_secret'
    private const WEBHOOK_SECRET = 'github.webhook_secret';

    private const GITHUB_SIGNATURE_HEADER = 'X-Hub-Signature-256';

    /**
     * Handles the incoming GitHub webhook request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deploy(Request $request)
    {
        // 1. Get Secret from config.services.php
        $secret = config(self::WEBHOOK_SECRET);

        // If secret is not set in .env, deployment is disabled or misconfigured.
        if (empty($secret)) {
            Log::error('GitHub Webhook secret is not configured. Set GITHUB_WEBHOOK_SECRET in your .env file.');

            return response()->json(['message' => 'Server misconfiguration'], 500);
        }

        // 2. Signature Check (Security Validation)
        $signature = $request->header(self::GITHUB_SIGNATURE_HEADER);

        if (! $signature || ! $this->verifySignature($signature, $request->getContent(), $secret)) {
            Log::warning('Unauthorized access attempt: Invalid signature.');

            return response()->json(['message' => 'Unauthorized or Invalid Signature'], 403);
        }

        // 3. Event Check
        $event = $request->header('X-GitHub-Event');

        if ($event === 'ping') {
            // Respond to GitHub's initial connection test
            return response()->json(['message' => 'Pong! Webhook successfully received.'], 200);
        }

        if ($event !== 'push') {
            // Ignore events other than 'push'
            Log::info("Ignoring non-push event: {$event}");

            return response()->json(['message' => "Ignoring event: {$event}"], 200);
        }

        // 4. Execute Deployment Script
        $scriptPath = base_path('deploy.sh');

        if (! file_exists($scriptPath)) {
            Log::error('Deployment script not found.', ['path' => $scriptPath]);

            return response()->json(['message' => 'deploy.sh not found'], 500);
        }

        // Using Process Facade for better control and error handling
        try {
            // Run the script and capture output/errors
            $process = Process::run($scriptPath);

            $output = $process->output();
            $errorOutput = $process->errorOutput();

            if ($process->failed()) {
                Log::error('Deployment script failed.', [
                    'exitCode' => $process->exitCode(),
                    'output' => $output,
                    'error' => $errorOutput,
                ]);

                return response()->json([
                    'message' => 'Deployment failed',
                    'output' => $output,
                    'error' => $errorOutput,
                ], 500);
            }

            Log::info('Deployment success.', ['output' => $output]);

            return response()->json([
                'message' => 'Deployment success',
                'output' => $output,
            ], 200);
        } catch (\Exception $e) {
            Log::critical('Deployment execution error.', ['exception' => $e->getMessage()]);

            return response()->json(['message' => 'Deployment execution error', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Verifies the GitHub Webhook signature.
     *
     * @param  string  $signature  The signature from the X-Hub-Signature-256 header.
     * @param  string  $payload  The raw request body.
     * @param  string  $secret  The GitHub Webhook secret key.
     */
    protected function verifySignature(string $signature, string $payload, string $secret): bool
    {
        // $signature format: 'sha256=HASH'
        if (strpos($signature, '=') === false) {
            return false;
        }

        [$algo, $hash] = explode('=', $signature, 2);

        // Ensure it's the expected algorithm
        if ($algo !== 'sha256') {
            return false;
        }

        // Generate the hash using the provided secret
        $payloadHash = hash_hmac($algo, $payload, $secret);

        // Use hash_equals() for timing-attack safe string comparison
        return hash_equals($hash, $payloadHash);
    }
}

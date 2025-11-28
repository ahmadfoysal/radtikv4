<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DeployController extends Controller
{

    private const DEPLOY_TOKEN = 'd9f1c0f3e6a94b8f93c72e51a7d4b28f9c3ad672';

    public function deploy(Request $request)
    {

        $incomingToken =
            $request->header('X-DEPLOY-TOKEN')
            ?? $request->input('token');

        if ($incomingToken !== self::DEPLOY_TOKEN) {
            Log::warning('Deploy webhook unauthorized', [
                'ip'    => $request->ip(),
                'token' => $incomingToken,
            ]);

            return response()->json(['message' => 'Unauthorized'], 403);
        }


        $event = $request->header('X-GitHub-Event');
        if ($event && $event !== 'push') {
            return response()->json(['message' => 'Event ignored'], 200);
        }


        $scriptPath = base_path('deploy.sh');

        if (! file_exists($scriptPath)) {
            Log::error('Deploy script not found', ['path' => $scriptPath]);

            return response()->json([
                'message' => 'Deploy script not found',
                'path'    => $scriptPath,
            ], 500);
        }


        try {
            $process = Process::fromShellCommandline($scriptPath);
            $process->setTimeout(300);

            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = $process->getOutput();

            Log::info('Deployment success', [
                'output' => $output,
            ]);

            return response()->json([
                'message' => 'Deployment success',
                'output'  => $output,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Deployment failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Deployment failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}

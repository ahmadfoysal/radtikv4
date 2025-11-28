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
        // Token check
        $incomingToken = $request->input('token');
        if ($incomingToken !== self::DEPLOY_TOKEN) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $scriptPath = base_path('deploy.sh');

        if (! file_exists($scriptPath)) {
            return response()->json(['message' => 'deploy.sh not found'], 500);
        }

        // Run script using exec() instead of Process
        exec($scriptPath . " 2>&1", $output, $return_var);

        if ($return_var !== 0) {
            return response()->json([
                'message' => 'Deployment failed',
                'output'  => $output,
            ], 500);
        }

        return response()->json([
            'message' => 'Deployment success',
            'output'  => $output,
        ], 200);
    }
}

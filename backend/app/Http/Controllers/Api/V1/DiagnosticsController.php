<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class DiagnosticsController extends Controller
{
    public function health(): JsonResponse
    {
        $databaseConnected = false;
        $database = null;

        try {
            DB::connection()->getPdo();
            $database = DB::connection()->getDatabaseName();
            $databaseConnected = true;
        } catch (Throwable) {
            // Keep diagnostics JSON-only even when the database is unavailable.
        }

        return response()->json([
            'status' => $databaseConnected ? 'ok' : 'error',
            'app_url' => config('app.url'),
            'database' => $database,
            'db_connected' => $databaseConnected,
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
        ], $databaseConnected ? 200 : 503);
    }

    public function debug(): JsonResponse
    {
        $databaseConnected = false;

        try {
            DB::connection()->getPdo();
            $databaseConnected = true;
        } catch (Throwable) {
            // Do not expose connection credentials or driver errors.
        }

        return response()->json([
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database_connected' => $databaseConnected,
            'app_url' => config('app.url'),
            'environment' => app()->environment(),
        ]);
    }
}

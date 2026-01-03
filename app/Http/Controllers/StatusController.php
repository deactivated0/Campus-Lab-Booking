<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StatusController
{
    public function index(Request $request)
    {
        // Only allow in local or debug mode
        if (! config('app.debug')) {
            abort(403);
        }

        $status = [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
        ];

        // DB reachable?
        try {
            DB::connection()->getPdo();
            $status['db'] = 'ok';
        } catch (\Throwable $e) {
            $status['db'] = 'unreachable';
            $status['db_error'] = $e->getMessage();
        }

        // Pending migrations
        try {
            $migrations = Artisan::call('migrate:status', ['--quiet' => true]);
            $status['migrations_checked'] = true;
        } catch (\Throwable $e) {
            $status['migrations_checked'] = false;
            $status['migrations_error'] = $e->getMessage();
        }

        // Redis health
        try {
            Redis::connection()->ping();
            $status['redis'] = 'ok';
        } catch (\Throwable $e) {
            $status['redis'] = 'unreachable';
            $status['redis_error'] = $e->getMessage();
        }

        // Queue (failed jobs count)
        try {
            $failed = DB::table('failed_jobs')->count();
            $status['failed_jobs'] = $failed;
        } catch (\Throwable $e) {
            $status['failed_jobs'] = 'unknown';
        }

        return response()->json($status);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function userRoleNames($user)
    {
        if (!$user || !Schema::hasTable('roles')) {
            Log::warning('Roles table missing or user is null when fetching role names', [
                'user_id' => $user?->id,
            ]);

            return collect([]);
        }

        try {
            return $user->getRoleNames();
        } catch (Throwable $e) {
            Log::warning('Failed to get role names for user', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    protected function userHasRole($user, $role): bool
    {
        if (!$user || !Schema::hasTable('roles')) {
            Log::warning('Roles table missing or user is null for hasRole check', [
                'user_id' => $user?->id,
                'role' => $role,
            ]);
            return false;
        }
        try {
            return $user->hasRole($role);
        } catch (Throwable $e) {
            Log::warning('Failed hasRole check', [
                'user_id' => $user->id ?? null,
                'role' => $role,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function userHasAnyRole($user, $roles): bool
    {
        if (!$user || !Schema::hasTable('roles')) {
            Log::warning('Roles table missing or user is null for hasAnyRole check', [
                'user_id' => $user?->id,
                'roles' => $roles,
            ]);
            return false;
        }
        try {
            return $user->hasAnyRole($roles);
        } catch (Throwable $e) {
            Log::warning('Failed hasAnyRole check', [
                'user_id' => $user->id ?? null,
                'roles' => $roles,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

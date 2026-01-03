<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Throwable;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        // Default values to avoid crashes when permission tables are missing
        $roles = [];
        $canApprove = false;
        $canAdmin = false;

        // Only attempt to query roles if the relevant DB tables exist and the Spatie classes are available
        if ($user) {
            try {
                if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles') && (class_exists(\Spatie\Permission\Models\Role::class) || trait_exists(\Spatie\Permission\Traits\HasRoles::class))) {
                    $roles = $user->getRoleNames();
                    $canApprove = $user->hasAnyRole(['Admin', 'LabStaff']);
                    $canAdmin = $user->hasRole('Admin');
                }
            } catch (Throwable $e) {
                // Log a warning so the developer knows permissions lookups failed
                logger()->warning('Could not load user roles (permissions tables may be missing or not migrated).', ['exception' => $e]);
                // fall back to safe defaults
            }
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ] : null,
                'roles' => $roles,
            ],
            'can' => [
                'approve' => $canApprove,
                'admin' => $canAdmin,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'qr' => fn () => $request->session()->get('qr'),
            ],
        ]);
    }
}

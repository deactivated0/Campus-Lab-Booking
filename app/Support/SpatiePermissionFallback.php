<?php
// This file provides a safe fallback for the Spatie HasRoles trait when
// spatie/laravel-permission is not installed. It declares the trait in the
// Spatie\Permission\Traits namespace and guards with trait_exists so it
// will never cause a redeclaration fatal when the real package is present.

namespace Spatie\Permission\Traits;

use Illuminate\Support\Collection;

// Only declare the fallback trait if the real one isn't available.
if (! trait_exists(\Spatie\Permission\Traits\HasRoles::class)) {
    trait HasRoles
    {
        // Basic no-op implementations to avoid fatal errors when the package is absent.

        public function assignRole(...$roles)
        {
            // No-op: return $this for chaining to keep calling code healthy.
            return $this;
        }

        public function removeRole($role)
        {
            return $this;
        }

        public function syncRoles($roles)
        {
            return $this;
        }

        public function hasRole($role): bool
        {
            return false;
        }

        public function hasAnyRole($roles): bool
        {
            return false;
        }

        public function hasAllRoles($roles): bool
        {
            return false;
        }

        public function getRoleNames(): Collection
        {
            return collect([]);
        }

        // Provide a lightweight stub relationship. If someone calls this when
        // the real Role model is absent it may still behave as an empty relation.
        public function roles()
        {
            return $this->belongsToMany('Spatie\\Permission\\Models\\Role', 'model_has_roles');
        }
    }
}


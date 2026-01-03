<?php

namespace App\Traits;

use Illuminate\Support\Collection;

if (trait_exists('\Spatie\Permission\Traits\HasRoles')) {
    trait HasRoles
    {
        use \Spatie\Permission\Traits\HasRoles;
    }
} else {
    trait HasRoles
    {
        // Minimal no-op implementations so app can run without spatie/laravel-permission.
        public function assignRole(...$roles)
        {
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

        // Provides a harmless relation stub to avoid errors if called.
        public function roles()
        {
            return $this->belongsToMany('Spatie\Permission\Models\Role', 'model_has_roles');
        }
    }
}

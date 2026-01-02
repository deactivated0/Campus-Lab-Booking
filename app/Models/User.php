<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles {
        getRoleNames as protected traitGetRoleNames;
        hasRole as protected traitHasRole;
        hasAnyRole as protected traitHasAnyRole;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'github_id',
        'facebook_id',
        'twitter_id',
        'apple_id',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Defensive wrappers to avoid fatal errors when permissions tables are missing or the DB is unreachable.
    public function getRoleNames()
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('roles')) {
                return collect([]);
            }

            return $this->traitGetRoleNames();
        } catch (\Throwable $e) {
            logger()->warning('User::getRoleNames failed (permissions tables may be missing).', ['exception' => $e]);
            return collect([]);
        }
    }

    public function hasRole($role): bool
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('roles')) {
                return false;
            }

            return $this->traitHasRole($role);
        } catch (\Throwable $e) {
            logger()->warning('User::hasRole failed (permissions tables may be missing).', ['exception' => $e]);
            return false;
        }
    }

    public function hasAnyRole($roles): bool
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('roles')) {
                return false;
            }

            return $this->traitHasAnyRole($roles);
        } catch (\Throwable $e) {
            logger()->warning('User::hasAnyRole failed (permissions tables may be missing).', ['exception' => $e]);
            return false;
        }
    }
}

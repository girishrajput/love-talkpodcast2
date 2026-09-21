<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'auth_user_id',
        'name',
        'email',
        'password',
        'avatar_url',
        'role',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => UserRole::class,
        'status' => UserStatus::class,
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Check if user is admin or super admin
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN || $this->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Check if user account is active
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Check if user has active premium access
     */
    public function hasPremiumAccess(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $activeMembership = $this->activeMembership;
        if (!$activeMembership) {
            return false;
        }

        return $activeMembership->status->value === 'active' && now()->lt($activeMembership->end_date);
    }

    /**
     * Relationships
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function activeMembership(): HasOne
    {
        return $this->hasOne(Membership::class)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->latestOfMany('end_date');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MembershipOrder::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}

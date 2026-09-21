<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membership extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'razorpay_customer_id',
        'razorpay_subscription_id',
        'start_date',
        'end_date',
        'auto_renew',
    ];

    protected $casts = [
        'status' => MembershipStatus::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isCurrentAndActive(): bool
    {
        return $this->status === MembershipStatus::ACTIVE && now()->lt($this->end_date);
    }
}

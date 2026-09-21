<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'target_audience',
        'price',
        'currency',
        'billing_period',
        'discounted_price',
        'is_active',
        'is_featured',
        'badge',
        'benefits',
        'razorpay_plan_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'benefits' => 'array',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'plan_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MembershipOrder::class, 'plan_id');
    }
}

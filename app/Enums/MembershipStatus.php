<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case PAUSED = 'paused';
}

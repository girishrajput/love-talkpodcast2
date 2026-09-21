<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case CAPTURED = 'captured';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case PENDING = 'pending';
}

<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case FAILED = 'failed';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

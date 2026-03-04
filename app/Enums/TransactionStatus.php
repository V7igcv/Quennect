<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case WAITING = 'WAITING';
    case SERVING = 'SERVING';
    case COMPLETED = 'COMPLETED';
    case SKIPPED = 'SKIPPED';
}

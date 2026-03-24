<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;

class NullSmsSender implements SmsSender
{
    public function send(string $to, string $message): bool
    {
        // Intentionally no-op for safe local development.
        return true;
    }
}

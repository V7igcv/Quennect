<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

class SmsApiPhSmsSender implements SmsSender
{
    public function send(string $to, string $message): bool
    {
        // Stub only for now: provider plumbing without outbound API calls yet.
        Log::warning('SMSAPI PH sender is not implemented yet.', [
            'to' => $to,
        ]);

        return false;
    }
}

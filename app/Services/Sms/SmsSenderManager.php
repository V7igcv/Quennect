<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;

class SmsSenderManager
{
    public function driver(?string $driver = null): SmsSender
    {
        return match ($driver ?? config('sms.driver', 'null')) {
            'smsapiph' => new SmsApiPhSmsSender(),
            'twilio' => new TwilioSmsSender(),
            'semaphore' => new SemaphoreSmsSender(),
            'null' => new NullSmsSender(),
            default => new NullSmsSender(),
        };
    }
}

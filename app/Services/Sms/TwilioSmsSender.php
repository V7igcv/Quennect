<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioSmsSender implements SmsSender
{
    public function send(string $to, string $message): bool
    {
        $baseUrl = rtrim((string) config('sms.twilio.base_url', ''), '/');
        $accountSid = (string) config('sms.twilio.account_sid', '');
        $authToken = (string) config('sms.twilio.auth_token', '');
        $fromNumber = (string) config('sms.twilio.from_number', '');
        $timeout = (int) config('sms.twilio.timeout', 10);

        if (empty($baseUrl) || empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            Log::warning('Twilio sender is not configured.', [
                'has_base_url' => !empty($baseUrl),
                'has_account_sid' => !empty($accountSid),
                'has_auth_token' => !empty($authToken),
                'has_from_number' => !empty($fromNumber),
            ]);

            return false;
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->asForm()
                ->timeout($timeout)
                ->withBasicAuth($accountSid, $authToken)
                ->post("/Accounts/{$accountSid}/Messages.json", [
                    'To' => $to,
                    'From' => $fromNumber,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Twilio request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'to' => $to,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('Twilio request exception.', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return false;
        }
    }
}

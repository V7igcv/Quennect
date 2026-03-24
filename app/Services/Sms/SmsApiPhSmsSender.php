<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsApiPhSmsSender implements SmsSender
{
    public function send(string $to, string $message): bool
    {
        $apiKey = config('sms.smsapiph.api_key');
        $sender = config('sms.smsapiph.sender');
        $baseUrl = rtrim((string) config('sms.smsapiph.base_url', ''), '/');
        $endpoint = '/' . ltrim((string) config('sms.smsapiph.endpoint', '/sms/send'), '/');
        $timeout = (int) config('sms.smsapiph.timeout', 10);

        if (empty($apiKey) || empty($baseUrl)) {
            Log::warning('SMSAPI PH sender is not configured.', [
                'has_api_key' => !empty($apiKey),
                'base_url' => $baseUrl,
            ]);

            return false;
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asForm()
                ->timeout($timeout)
                ->post($endpoint, [
                    'api_key' => $apiKey,
                    'sender' => $sender,
                    'number' => $to,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                $json = $response->json();

                if (is_array($json)) {
                    if (array_key_exists('success', $json)) {
                        return (bool) $json['success'];
                    }

                    if (array_key_exists('status', $json)) {
                        return in_array(strtolower((string) $json['status']), ['ok', 'success', 'sent'], true);
                    }
                }

                return true;
            }

            Log::warning('SMSAPI PH request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'to' => $to,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('SMSAPI PH request exception.', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return false;
        }

    }
}

<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SemaphoreSmsSender implements SmsSender
{
    public function send(string $to, string $message): bool
    {
        $apiKey = (string) config('sms.semaphore.api_key', '');
        $sender = (string) config('sms.semaphore.sender', '');
        $baseUrl = rtrim((string) config('sms.semaphore.base_url', ''), '/');
        $endpoint = '/' . ltrim((string) config('sms.semaphore.endpoint', '/messages'), '/');
        $timeout = (int) config('sms.semaphore.timeout', 10);

        if (empty($apiKey) || empty($baseUrl)) {
            Log::warning('Semaphore sender is not configured.', [
                'has_api_key' => !empty($apiKey),
                'base_url' => $baseUrl,
            ]);

            return false;
        }

        $payload = [
            'apikey' => $apiKey,
            'number' => preg_replace('/\D+/', '', $to),
            'message' => $message,
        ];

        if (!empty($sender)) {
            $payload['sendername'] = $sender;
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asForm()
                ->timeout($timeout)
                ->post($endpoint, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Semaphore request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'to' => $to,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('Semaphore request exception.', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return false;
        }

    }
}

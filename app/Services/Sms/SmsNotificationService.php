<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    public function __construct(private SmsSender $smsSender)
    {
    }

    public function sendEvaluationSubmittedMessage(
        string $contactNumber,
        string $queueNumber,
        ?string $officeName = null
    ): bool {
        $normalizedNumber = $this->normalizePhoneNumber($contactNumber);

        if (!$normalizedNumber) {
            Log::warning('SMS not sent: invalid contact number.', [
                'contact_number' => $contactNumber,
                'queue_number' => $queueNumber,
            ]);

            return false;
        }

        $message = $this->buildEvaluationSubmittedMessage($queueNumber, $officeName);

        return $this->smsSender->send($normalizedNumber, $message);
    }

    public function buildEvaluationSubmittedMessage(string $queueNumber, ?string $officeName = null): string
    {
        $office = $officeName ?: config('app.name');

        return sprintf(
            'Salamat po! Naisumite na ang inyong evaluation para sa queue %s sa %s. Ingat po at maraming salamat.',
            $queueNumber,
            $office
        );
    }

    private function normalizePhoneNumber(string $contactNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $contactNumber);

        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '+63' . substr($digits, 1);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '+63' . $digits;
        }

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '+' . $digits;
        }

        return null;
    }
}

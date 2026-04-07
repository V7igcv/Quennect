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
        string $clientName,
        string $services,
        ?float $averageRating,
        ?string $officeName = null
    ): bool {
        $normalizedNumber = $this->normalizePhoneNumber($contactNumber);

        if (!$normalizedNumber) {
            Log::warning('SMS not sent: invalid contact number.', [
                'contact_number' => $contactNumber,
                'client_name' => $clientName,
            ]);

            return false;
        }

        $message = $this->buildEvaluationSubmittedMessage(
            $clientName,
            $services,
            $averageRating,
            $officeName
        );

        return $this->smsSender->send($normalizedNumber, $message);
    }

    public function buildEvaluationSubmittedMessage(
        string $clientName,
        string $services,
        ?float $averageRating,
        ?string $officeName = null
    ): string
    {
        $office = $officeName ?: 'Ligao City Hall';

        // Determine sentiment based on average satisfaction rating
        // Treat Neutral (3) and above as positive; below 3 or null as improvement feedback
        $isPositive = $averageRating !== null && $averageRating >= 3.0;

        if ($isPositive) {
            // Strongly Agree / Agree / Neutral
            return "Salamat po, {$clientName}! Lubos po naming pinahahalagahan ang inyong positibong karanasan sa inyong transaksyon sa {$services}. Ang inyong feedback ay nagbibigay sa amin ng inspirasyon upang lalo pang pagbutihin ang serbisyo sa {$office}. Hanggang sa muli!";
        }

        // Strongly Disagree / Disagree / Not Applicable (or no computed rating)
        return "Salamat po, {$clientName}, sa inyong pagbabahagi ng inyong karanasan sa inyong transaksyon sa {$services}. Sisiguruhin po naming gagalingan pa namin ang serbisyo sa {$office} sa susunod na pagkakataon. Hanggang sa muli!";
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

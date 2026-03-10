<?php
// app/Http/Requests/Frontdesk/CallQueueRequest.php

namespace App\Http\Requests\Frontdesk;

use Illuminate\Foundation\Http\FormRequest;

class CallQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'counter_id' => 'required|integer|exists:counters,id'
        ];
    }

    public function messages(): array
    {
        return [
            'counter_id.required' => 'Please select a counter.',
            'counter_id.exists' => 'Selected counter is invalid or does not exist.'
        ];
    }
}
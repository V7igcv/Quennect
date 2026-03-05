<?php

namespace App\Http\Requests\Kiosk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateQueueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Kiosk is public, so always true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'office_id' => [
                'required',
                'integer',
                Rule::exists('offices', 'id')->where('is_active', true)
            ],
            'client_name' => [
                'required',
                'string',
                'max:150',
                'regex:/^[a-zA-Z\s\.,\-]+$/' // Allows letters, spaces, dots, commas, hyphens
            ],
            'contact_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^(09|\+639)\d{9}$/' // Philippine mobile number format
            ],
            'barangay_id' => [
                'required',
                'integer',
                Rule::exists('barangays', 'id')
            ],
            'lane_type' => [
                'required',
                'string',
                Rule::in(['regular', 'priority'])
            ],
            'service_ids' => [
                'required',
                'array',
                'min:1'
            ],
            'service_ids.*' => [
                'integer',
                Rule::exists('services', 'id')->where(function ($query) {
                    $query->where('office_id', $this->office_id);
                })
            ],
            'priority_sector_ids' => [
                'required_if:lane_type,priority',
                'prohibited_if:lane_type,regular',
                'array',
                'min:1'
            ],
            'priority_sector_ids.*' => [
                'integer',
                Rule::exists('priority_sectors', 'id')
            ]
        ];
    }

    /**
     * Custom error messages in Filipino
     */
    public function messages(): array
    {
        return [
            'office_id.required' => 'Pumili po ng opisina.',
            'office_id.exists' => 'Hindi available ang napiling opisina.',
            'client_name.required' => 'Ilagay po ang inyong buong pangalan.',
            'client_name.regex' => 'Gumamit lamang po ng letra, tuldok, at kuwit.',
            'contact_number.required' => 'Ilagay po ang inyong contact number.',
            'contact_number.regex' => 'Gumamit po ng tamang format: 09123456789 o +639123456789.',
            'barangay_id.required' => 'Pumili po ng inyong barangay.',
            'lane_type.required' => 'Pumili po ng uri ng lane.',
            'lane_type.in' => 'Regular o Priority lamang po ang pagpipilian.',
            'service_ids.required' => 'Pumili po ng kahit isang serbisyo.',
            'service_ids.min' => 'Pumili po ng kahit isang serbisyo.',
            'service_ids.*.exists' => 'Hindi available ang napiling serbisyo.',
            'priority_sector_ids.required_if' => 'Pumili po ng inyong sektor.',
            'priority_sector_ids.prohibited_if' => 'Hindi po kailangan ng priority sector para sa regular lane.'
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Convert lane_type to is_priority boolean
        if ($this->has('lane_type')) {
            $this->merge([
                'is_priority' => $this->lane_type === 'priority'
            ]);
        }
    }

    /**
     * Configure the validator instance
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if office has available services
            $officeId = $this->office_id;
            $serviceIds = $this->service_ids ?? [];

            if ($officeId && !empty($serviceIds)) {
                $validServices = \App\Models\Service::where('office_id', $officeId)
                    ->whereIn('id', $serviceIds)
                    ->count();

                if ($validServices !== count($serviceIds)) {
                    $validator->errors()->add(
                        'service_ids',
                        'Ang ilang serbisyo ay hindi kabilang sa napiling opisina.'
                    );
                }
            }
        });
    }
}
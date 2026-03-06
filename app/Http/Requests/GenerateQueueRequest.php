<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateQueueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
                'regex:/^[a-zA-Z\s\.,\-]+$/'
            ],
            'contact_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^(09|\+639)\d{9}$/'
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
                'sometimes',
                'array',
                'nullable'
            ],
            'priority_sector_ids.*' => [
                'integer',
                Rule::exists('priority_sectors', 'id')
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
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
            'barangay_id.exists' => 'Hindi available ang napiling barangay.',
            'lane_type.required' => 'Pumili po ng uri ng lane.',
            'lane_type.in' => 'Regular o Priority lamang po ang pagpipilian.',
            'service_ids.required' => 'Pumili po ng kahit isang serbisyo.',
            'service_ids.min' => 'Pumili po ng kahit isang serbisyo.',
            'service_ids.*.exists' => 'Hindi available ang napiling serbisyo para sa opisina na ito.',
            'priority_sector_ids.*.exists' => 'Hindi available ang napiling priority sector.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // I-merge ang is_priority based sa lane_type
        if ($this->has('lane_type')) {
            $this->merge([
                'is_priority' => $this->lane_type === 'priority'
            ]);
        }
        
        // I-merge ang priority_sector_ids kung walang laman
        if (!$this->has('priority_sector_ids')) {
            $this->merge([
                'priority_sector_ids' => []
            ]);
        }
    }

    /**
     * Get the validated data from the request.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();
        
        // Siguraduhing may is_priority
        if (!isset($validated['is_priority']) && $this->has('lane_type')) {
            $validated['is_priority'] = $this->lane_type === 'priority';
        }
        
        // Siguraduhing may priority_sector_ids
        if (!isset($validated['priority_sector_ids'])) {
            $validated['priority_sector_ids'] = [];
        }
        
        return $key ? ($validated[$key] ?? $default) : $validated;
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        $this->replace($this->validated());
    }
}
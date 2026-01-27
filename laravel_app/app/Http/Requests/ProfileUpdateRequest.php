<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'medical_center_name' => ['nullable', 'string', 'max:255'],
            'degrees' => ['nullable', 'string'],
            'designation' => ['nullable', 'string', 'max:255'],
            'additional_qualifications' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'clinic_address' => ['nullable', 'string', 'max:500'],
            'clinic_contact_number' => ['nullable', 'string', 'max:50'],
            'clinic_email' => ['nullable', 'string', 'email', 'max:255'],
            'clinic_registration_number' => ['nullable', 'string', 'max:255'],
            'clinic_gstin' => ['nullable', 'string', 'max:255'],
        ];
    }
}

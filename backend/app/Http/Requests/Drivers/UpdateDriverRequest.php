<?php

namespace App\Http\Requests\Drivers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $driverId = $this->route('driver')?->id;
        $licenseId = $this->route('driver')?->license?->id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'identity_document' => ['required', 'string', 'max:50', Rule::unique('drivers', 'identity_document')->ignore($driverId)],
            'tss_worker_code' => ['required', 'string', 'max:40', Rule::unique('drivers', 'tss_worker_code')->ignore($driverId)],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'contract_type_id' => ['nullable', 'exists:contract_types,id'],
            'hire_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,resignation,dismissal'],
            'rehire_status' => ['required', 'in:yes,no,review'],
            'rehire_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'license.license_number' => ['nullable', 'string', 'max:50', Rule::unique('driver_licenses', 'license_number')->ignore($licenseId)],
            'license.category' => ['nullable', 'string', 'max:30'],
            'license.issued_at' => ['nullable', 'date'],
            'license.expires_at' => ['nullable', 'date', 'after_or_equal:license.issued_at'],
            'license.issuing_entity' => ['nullable', 'string', 'max:120'],
            'license.restrictions' => ['nullable', 'string'],
            'license.observations' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'identity_document' => $this->formatDominicanDocument($this->input('identity_document')),
            'license' => [
                ...($this->input('license', []) ?? []),
                'license_number' => $this->formatDominicanDocument($this->input('identity_document')),
            ],
        ]);
    }

    private function formatDominicanDocument(?string $value): ?string
    {
        if (! $value) {
            return $value;
        }

        $digits = substr(preg_replace('/\D/', '', $value), 0, 11);

        if (strlen($digits) !== 11) {
            return $value;
        }

        return substr($digits, 0, 3).'-'.substr($digits, 3, 7).'-'.substr($digits, 10, 1);
    }
}

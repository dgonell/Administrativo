<?php

namespace App\Services;

class TrafficFineLookupService
{
    public function lookupByLicense(string $licenseNumber): array
    {
        return [
            'status' => 'manual_review_required',
            'message' => 'No se ha configurado una fuente oficial para consultar infracciones de transito.',
            'license_number' => $licenseNumber,
        ];
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DriverCatalogSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Driver;
use Tests\TestCase;

class DriverApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_lists_drivers(): void
    {
        $this->authenticate();

        $payload = [
            'first_name' => 'Carlos',
            'last_name' => 'Rojas',
            'code' => 'MANUAL-999',
            'identity_document' => '1234567',
            'tss_worker_code' => 'TSS-900001',
            'phone' => '70000000',
            'contact_name' => 'Ana Rojas',
            'status' => 'active',
            'rehire_status' => 'review',
            'license' => [
                'license_number' => 'LIC-123',
                'category' => 'C',
                'issued_at' => '2022-01-01',
                'expires_at' => '2027-01-01',
            ],
        ];

        $this->postJson('/api/drivers', $payload)
            ->assertCreated()
            ->assertJsonPath('code', 'CH1')
            ->assertJsonPath('license.license_number', '1234567');

        $this->getJson('/api/drivers')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'CH1');
    }

    public function test_it_registers_driver_module_records(): void
    {
        $this->authenticate();

        $driver = $this->postJson('/api/drivers', [
            'code' => 'MANUAL-002',
            'first_name' => 'Mario',
            'last_name' => 'Mendoza',
            'identity_document' => '7654321',
            'tss_worker_code' => 'TSS-900002',
            'contact_name' => 'Laura Mendoza',
            'status' => 'active',
            'rehire_status' => 'review',
        ])->json();

        $this->postJson("/api/drivers/{$driver['id']}/documents", [
            'document_type' => 'identity',
            'name' => 'Cedula',
            'file_path' => 'drivers/CH2/cedula.pdf',
            'status' => 'valid',
        ])->assertCreated();

        $this->postJson("/api/drivers/{$driver['id']}/medical-leaves", [
            'leave_type' => 'medical',
            'started_at' => '2026-05-01',
            'ended_at' => '2026-05-03',
            'status' => 'approved',
        ])->assertCreated();

        $this->postJson("/api/drivers/{$driver['id']}/conduct-reports", [
            'event_date' => '2026-05-05',
            'type' => 'complaint',
            'severity' => 'medium',
            'description' => 'Queja registrada por operaciones.',
            'status' => 'open',
        ])->assertCreated();

        $this->postJson("/api/drivers/{$driver['id']}/termination-records", [
            'termination_date' => '2026-05-10',
            'termination_type' => 'resignation',
            'reason' => 'Renuncia voluntaria',
            'rehire_status' => 'yes',
        ])->assertCreated();

        $this->getJson("/api/drivers/{$driver['id']}")
            ->assertOk()
            ->assertJsonCount(1, 'documents')
            ->assertJsonCount(1, 'medical_leaves')
            ->assertJsonCount(1, 'conduct_reports')
            ->assertJsonCount(1, 'termination_records')
            ->assertJsonCount(2, 'status_histories')
            ->assertJsonPath('status', 'resignation');

        $this->postJson("/api/drivers/{$driver['id']}/rehire", [
            'hire_date' => '2026-06-01',
            'reason' => 'Vacante aprobada por operaciones',
            'rehire_status' => 'yes',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('termination_date', null)
            ->assertJsonCount(3, 'status_histories');
    }

    public function test_it_seeds_twenty_four_complete_dominican_drivers(): void
    {
        $this->authenticate();
        $this->seed(DriverCatalogSeeder::class);

        $response = $this->getJson('/api/drivers')
            ->assertOk()
            ->assertJsonPath('total', 24);

        $driverId = Driver::query()->where('identity_document', '001-1857426-3')->value('id');

        $this->getJson("/api/drivers/{$driverId}")
            ->assertOk()
            ->assertJsonPath('license.issuing_entity', 'Direccion General de Seguridad de Transito y Transporte Terrestre')
            ->assertJsonCount(6, 'documents')
            ->assertJsonCount(1, 'medical_leaves')
            ->assertJsonCount(1, 'conduct_reports')
            ->assertJsonCount(1, 'traffic_fine_checks');
    }

    public function test_it_uploads_driver_photo(): void
    {
        $this->authenticate();
        Storage::fake('public');

        $driver = $this->postJson('/api/drivers', [
            'first_name' => 'Mario',
            'last_name' => 'Mendoza',
            'identity_document' => '7654321',
            'tss_worker_code' => 'TSS-900003',
            'contact_name' => 'Laura Mendoza',
            'status' => 'active',
            'rehire_status' => 'review',
        ])->json();

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

        $response = $this->post("/api/drivers/{$driver['id']}/photo", [
            'photo' => UploadedFile::fake()->createWithContent('chofer.png', $png),
        ]);

        $response->assertOk()
            ->assertJsonPath('id', $driver['id']);

        Storage::disk('public')->assertExists($response->json('photo_path'));
    }
}

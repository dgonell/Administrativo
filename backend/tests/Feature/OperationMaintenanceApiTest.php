<?php

namespace Tests\Feature;

use App\Models\OperationBus;
use Database\Seeders\OperationPartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationMaintenanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_maintenance_items_and_updates_bus_mileage(): void
    {
        $this->authenticate();
        $this->seed(OperationPartSeeder::class);
        $bus = OperationBus::query()->create([
            'fleet_number' => '300',
            'brand' => 'Scania',
            'model' => 'K410',
            'vehicle_type' => 'bus',
            'plate' => 'I300001',
            'status' => 'operational',
        ]);

        $this->getJson('/api/operation-maintenance-catalogs')
            ->assertOk()
            ->assertJsonCount(12, 'parts');

        $this->postJson('/api/operation-maintenances', [
            'operation_bus_id' => $bus->id,
            'service_type' => 'oil_change',
            'service_date' => '2026-06-02',
            'mileage' => 450000,
            'workshop' => 'Taller interno',
            'next_due_mileage' => 465000,
            'items' => [
                ['category' => 'oil', 'name' => 'Aceite de motor', 'quantity' => 8, 'unit_cost' => 450],
                ['category' => 'filter', 'name' => 'Filtro de aceite', 'quantity' => 1, 'unit_cost' => 1800],
            ],
        ])
            ->assertCreated()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.name', 'Aceite de motor');

        $this->assertDatabaseHas('operation_buses', [
            'id' => $bus->id,
            'current_mileage' => 450000,
        ]);
        $this->assertDatabaseHas('operation_bus_histories', [
            'operation_bus_id' => $bus->id,
            'action' => 'Mantenimiento registrado',
        ]);
    }

    public function test_tire_change_requires_a_position(): void
    {
        $this->authenticate();
        $bus = OperationBus::query()->create([
            'fleet_number' => '301',
            'brand' => 'KIA',
            'model' => 'Grandbird',
            'vehicle_type' => 'bus',
            'plate' => 'I300002',
            'status' => 'operational',
        ]);

        $this->postJson('/api/operation-maintenances', [
            'operation_bus_id' => $bus->id,
            'service_type' => 'tire_change',
            'service_date' => '2026-06-02',
            'items' => [
                ['category' => 'tire', 'name' => 'Neumatico', 'quantity' => 1],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Debes indicar la posicion de cada neumatico.');
    }

    public function test_tire_change_rejects_duplicate_positions(): void
    {
        $this->authenticate();
        $bus = OperationBus::query()->create([
            'fleet_number' => '302',
            'brand' => 'KIA',
            'model' => 'Grandbird',
            'vehicle_type' => 'bus',
            'plate' => 'I300003',
            'status' => 'operational',
        ]);

        $this->postJson('/api/operation-maintenances', [
            'operation_bus_id' => $bus->id,
            'service_type' => 'tire_change',
            'service_date' => '2026-06-02',
            'items' => [
                ['category' => 'tire', 'name' => 'Neumatico', 'quantity' => 1, 'tire_position' => 'Delantero izquierdo'],
                ['category' => 'tire', 'name' => 'Neumatico', 'quantity' => 1, 'tire_position' => 'Delantero izquierdo'],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes registrar dos neumaticos para la misma posicion en un servicio.');
    }

    public function test_it_rejects_incoherent_maintenance_intervals(): void
    {
        $this->authenticate();
        $bus = OperationBus::query()->create([
            'fleet_number' => '303',
            'brand' => 'Scania',
            'model' => 'K410',
            'vehicle_type' => 'bus',
            'plate' => 'I300004',
            'status' => 'operational',
            'current_mileage' => 500000,
        ]);

        $this->postJson('/api/operation-maintenances', [
            'operation_bus_id' => $bus->id,
            'service_type' => 'oil_change',
            'service_date' => '2026-06-02',
            'mileage' => 490000,
            'items' => [
                ['category' => 'oil', 'name' => 'Aceite de motor', 'quantity' => 8],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El kilometraje del mantenimiento no puede ser menor al kilometraje actual de la unidad.');

        $this->postJson('/api/operation-maintenances', [
            'operation_bus_id' => $bus->id,
            'service_type' => 'oil_change',
            'service_date' => '2026-06-02',
            'next_due_mileage' => 490000,
            'items' => [
                ['category' => 'oil', 'name' => 'Aceite de motor', 'quantity' => 8],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El proximo kilometraje debe ser mayor o igual al kilometraje actual.');
    }
}

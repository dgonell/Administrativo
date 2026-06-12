<?php

namespace Tests\Feature;

use App\Models\FuelHose;
use App\Models\FuelTank;
use App\Models\OperationBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_controls_fuel_inventory_dispatches_and_metrics(): void
    {
        $this->authenticate();

        $tank = $this->postJson('/api/fuel-tanks', [
            'name' => 'Tanque principal',
            'fuel_type' => 'diesel',
            'capacity_liters' => 10000,
            'current_liters' => 0,
            'minimum_liters' => 1200,
            'status' => 'active',
        ])
            ->assertCreated()
            ->assertJsonPath('name', 'Tanque principal')
            ->json();

        $hose = $this->postJson('/api/fuel-hoses', [
            'fuel_tank_id' => $tank['id'],
            'name' => 'Manguera 1',
            'current_counter' => 1000,
            'allowed_difference_liters' => 0.5,
            'status' => 'active',
        ])
            ->assertCreated()
            ->assertJsonPath('code', 'M-1')
            ->json();

        $this->postJson('/api/fuel-purchases', [
            'fuel_tank_id' => $tank['id'],
            'received_at' => '2026-06-12',
            'supplier' => 'Proveedor Diesel',
            'invoice_number' => 'F-100',
            'liters' => 5000,
            'unit_cost' => 52.5,
        ])->assertCreated()->assertJsonPath('liters', '5000.00');

        $bus = OperationBus::query()->create([
            'fleet_number' => '300',
            'brand' => 'Mercedes-Benz',
            'model' => 'OF',
            'vehicle_type' => 'bus',
            'plate' => 'A300001',
            'capacity' => 60,
            'status' => 'operational',
        ]);

        $dispatch = $this->postJson('/api/fuel-dispatches', [
            'fuel_hose_id' => $hose['id'],
            'recipient_type' => 'company_bus',
            'operation_bus_id' => $bus->id,
            'dispatched_at' => '2026-06-12 08:30:00',
            'hose_counter_start' => 1000,
            'hose_counter_end' => 1125.5,
            'odometer' => 120000,
            'operator_name' => 'Operador',
            'authorized_by' => 'Supervisor',
        ])
            ->assertCreated()
            ->assertJsonPath('liters', '125.500')
            ->assertJsonPath('status', 'posted')
            ->json();

        $this->assertSame('4874.50', FuelTank::query()->find($tank['id'])->current_liters);
        $this->assertSame('1125.500', FuelHose::query()->find($hose['id'])->current_counter);

        $this->getJson('/api/fuel/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.stock_liters', 4874.5)
            ->assertJsonPath('metrics.month_liters', 125.5);

        $this->patchJson("/api/fuel-dispatches/{$dispatch['id']}/void", [
            'void_reason' => 'Error de prueba',
        ])->assertOk()->assertJsonPath('status', 'void');

        $this->assertSame('5000.00', FuelTank::query()->find($tank['id'])->current_liters);
    }
}

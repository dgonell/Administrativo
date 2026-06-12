<?php

namespace Database\Seeders;

use App\Models\OperationBus;
use App\Models\OperationMaintenance;
use App\Models\OperationMaintenanceItem;
use App\Models\OperationPart;
use Illuminate\Database\Seeder;

class OperationMaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $nearDates = [
            ['service_date' => now()->subDays(2)->toDateString(), 'next_due_date' => now()->addDays(28)->toDateString()],
            ['service_date' => now()->subDays(6)->toDateString(), 'next_due_date' => now()->addDays(24)->toDateString()],
            ['service_date' => now()->subDays(10)->toDateString(), 'next_due_date' => now()->addDays(18)->toDateString()],
            ['service_date' => now()->subDays(14)->toDateString(), 'next_due_date' => now()->addDays(12)->toDateString()],
        ];

        $records = [
            [
                'fleet_number' => '104',
                'service_type' => 'oil_change',
                'service_date' => $nearDates[0]['service_date'],
                'mileage' => 142500,
                'workshop' => 'Taller Central',
                'technician' => 'Carlos Mendez',
                'labor_cost' => 1850.00,
                'next_due_date' => $nearDates[0]['next_due_date'],
                'next_due_mileage' => 148000,
                'notes' => 'Cambio de aceite y revisión de filtros.',
                'items' => [
                    ['name' => 'Aceite de motor', 'category' => 'oil', 'quantity' => 8, 'unit_cost' => 350.00],
                    ['name' => 'Filtro de aceite', 'category' => 'filter', 'quantity' => 1, 'unit_cost' => 420.00],
                ],
            ],
            [
                'fleet_number' => '111',
                'service_type' => 'filter_change',
                'service_date' => $nearDates[1]['service_date'],
                'mileage' => 186300,
                'workshop' => 'Mantenimiento VIP',
                'technician' => 'Ana Ruiz',
                'labor_cost' => 980.00,
                'next_due_date' => $nearDates[1]['next_due_date'],
                'next_due_mileage' => 192000,
                'notes' => 'Revisión de sistema de aire y reemplazo de filtro de aceite.',
                'items' => [
                    ['name' => 'Filtro de aceite', 'category' => 'filter', 'quantity' => 1, 'unit_cost' => 420.00],
                    ['name' => 'Filtro de aire', 'category' => 'filter', 'quantity' => 1, 'unit_cost' => 520.00],
                ],
            ],
            [
                'fleet_number' => '137',
                'service_type' => 'tire_change',
                'service_date' => $nearDates[2]['service_date'],
                'mileage' => 205000,
                'workshop' => 'Neumáticos del Norte',
                'technician' => 'Luis Santos',
                'labor_cost' => 2200.00,
                'next_due_date' => $nearDates[2]['next_due_date'],
                'next_due_mileage' => 210000,
                'notes' => 'Reemplazo de neumáticos delanteros y balanceo.',
                'items' => [
                    ['name' => 'Neumatico', 'category' => 'tire', 'quantity' => 2, 'unit_cost' => 1450.00, 'tire_position' => 'Delantero izquierdo'],
                    ['name' => 'Neumatico', 'category' => 'tire', 'quantity' => 2, 'unit_cost' => 1450.00, 'tire_position' => 'Delantero derecho'],
                ],
            ],
            [
                'fleet_number' => '140',
                'service_type' => 'preventive',
                'service_date' => $nearDates[3]['service_date'],
                'mileage' => 158700,
                'workshop' => 'Flota Service',
                'technician' => 'Mariana Paredes',
                'labor_cost' => 1200.00,
                'next_due_date' => $nearDates[3]['next_due_date'],
                'next_due_mileage' => 164500,
                'notes' => 'Mantenimiento preventivo completo de sistema de frenos y suspensión.',
                'items' => [
                    ['name' => 'Pastillas de freno', 'category' => 'part', 'quantity' => 4, 'unit_cost' => 620.00],
                    ['name' => 'Correa de motor', 'category' => 'part', 'quantity' => 1, 'unit_cost' => 980.00],
                ],
            ],
        ];

        foreach ($records as $record) {
            $bus = OperationBus::query()->where('fleet_number', $record['fleet_number'])->first();
            if (! $bus) {
                continue;
            }

            $maintenance = OperationMaintenance::query()->updateOrCreate(
                [
                    'operation_bus_id' => $bus->id,
                    'service_type' => $record['service_type'],
                ],
                [
                    'service_date' => $record['service_date'],
                    'mileage' => $record['mileage'],
                    'workshop' => $record['workshop'],
                    'technician' => $record['technician'],
                    'labor_cost' => $record['labor_cost'],
                    'next_due_date' => $record['next_due_date'],
                    'next_due_mileage' => $record['next_due_mileage'],
                    'notes' => $record['notes'],
                ]
            );

            OperationMaintenanceItem::query()->where('operation_maintenance_id', $maintenance->id)->delete();

            $items = [];
            foreach ($record['items'] as $itemData) {
                $part = OperationPart::query()->where('name', $itemData['name'])->first();
                $items[] = array_merge($itemData, [
                    'operation_part_id' => $part?->id,
                    'operation_maintenance_id' => $maintenance->id,
                ]);
            }

            $maintenance->items()->createMany($items);
        }
    }
}

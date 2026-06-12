<?php

namespace Database\Seeders;

use App\Models\OperationBus;
use App\Models\OperationBusHistory;
use App\Models\OperationMaintenance;
use App\Models\OperationPart;
use Illuminate\Database\Seeder;

class OperationMaintenanceSampleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OperationPartSeeder::class);

        $parts = OperationPart::query()->get()->keyBy('name');
        $tirePositions = [
            'Delantero izquierdo',
            'Delantero derecho',
            'Trasero izquierdo exterior',
            'Trasero izquierdo interior',
            'Trasero derecho exterior',
            'Trasero derecho interior',
        ];

        OperationBus::query()
            ->orderBy('fleet_number')
            ->get()
            ->each(function (OperationBus $bus, int $index) use ($parts, $tirePositions) {
                $baseMileage = $bus->current_mileage ?: $this->estimatedMileage($bus, $index);
                $oilMileage = $baseMileage + 1200;
                $secondaryMileage = $oilMileage + 1800;
                $firstDate = now()->subDays(40 - min($index, 18))->toDateString();
                $secondDate = now()->subDays(18 - min($index, 15))->toDateString();

                $this->createMaintenance($bus, [
                    'service_type' => 'oil_change',
                    'service_date' => $firstDate,
                    'mileage' => $oilMileage,
                    'workshop' => 'Taller interno SODASA',
                    'technician' => $this->technician($index),
                    'labor_cost' => 1800,
                    'next_due_date' => now()->addDays(50 + $index)->toDateString(),
                    'next_due_mileage' => $oilMileage + 15000,
                    'notes' => 'Registro preventivo de ejemplo: cambio de aceite y filtros.',
                    'items' => [
                        ['part' => $parts['Aceite de motor'] ?? null, 'category' => 'oil', 'name' => 'Aceite de motor', 'quantity' => 8, 'unit_cost' => 450],
                        ['part' => $parts['Filtro de aceite'] ?? null, 'category' => 'filter', 'name' => 'Filtro de aceite', 'quantity' => 1, 'unit_cost' => 1850],
                        ['part' => $parts['Filtro de combustible'] ?? null, 'category' => 'filter', 'name' => 'Filtro de combustible', 'quantity' => 1, 'unit_cost' => 2250],
                    ],
                ]);

                if ($index % 2 === 0) {
                    $position = $tirePositions[$index % count($tirePositions)];
                    $this->createMaintenance($bus, [
                        'service_type' => 'tire_change',
                        'service_date' => $secondDate,
                        'mileage' => $secondaryMileage,
                        'workshop' => 'Taller interno SODASA',
                        'technician' => $this->technician($index + 1),
                        'labor_cost' => 1200,
                        'next_due_date' => now()->addDays(80 + $index)->toDateString(),
                        'notes' => "Cambio preventivo de neumatico en posicion {$position}.",
                        'items' => [
                            [
                                'part' => $parts['Neumatico'] ?? null,
                                'category' => 'tire',
                                'name' => 'Neumatico',
                                'quantity' => 1,
                                'tire_position' => $position,
                                'brand' => $this->tireBrand($index),
                                'reference' => '295/80R22.5',
                                'unit_cost' => 18500,
                            ],
                        ],
                    ]);
                } else {
                    $partName = $this->partName($index);
                    $this->createMaintenance($bus, [
                        'service_type' => 'part_replacement',
                        'service_date' => $secondDate,
                        'mileage' => $secondaryMileage,
                        'workshop' => 'Taller interno SODASA',
                        'technician' => $this->technician($index + 1),
                        'labor_cost' => 2500,
                        'next_due_mileage' => $secondaryMileage + 20000,
                        'notes' => "Sustitucion preventiva de {$partName}.",
                        'items' => [
                            [
                                'part' => $parts[$partName] ?? null,
                                'category' => 'part',
                                'name' => $partName,
                                'quantity' => 1,
                                'unit_cost' => $this->partCost($partName),
                            ],
                        ],
                    ]);
                }

                $bus->update([
                    'current_mileage' => max($bus->current_mileage ?? 0, $secondaryMileage),
                    'mileage_updated_at' => $secondDate,
                ]);
            });
    }

    private function createMaintenance(OperationBus $bus, array $data): void
    {
        $exists = OperationMaintenance::query()
            ->where('operation_bus_id', $bus->id)
            ->where('service_type', $data['service_type'])
            ->where('notes', $data['notes'])
            ->exists();

        if ($exists) {
            return;
        }

        $items = collect($data['items'])->map(fn (array $item) => [
            'operation_part_id' => $item['part']?->id,
            'category' => $item['category'],
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'tire_position' => $item['tire_position'] ?? null,
            'brand' => $item['brand'] ?? null,
            'reference' => $item['reference'] ?? null,
            'unit_cost' => $item['unit_cost'] ?? 0,
            'notes' => $item['notes'] ?? null,
        ])->all();

        unset($data['items']);

        $maintenance = OperationMaintenance::query()->create([
            'operation_bus_id' => $bus->id,
            ...$data,
        ]);
        $maintenance->items()->createMany($items);

        OperationBusHistory::query()->create([
            'operation_bus_id' => $bus->id,
            'fleet_number' => $bus->fleet_number,
            'action' => 'Mantenimiento registrado',
            'detail' => $this->summary($maintenance),
        ]);
    }

    private function estimatedMileage(OperationBus $bus, int $index): int
    {
        $year = $bus->year ?: 2014;

        return max(95000, ((int) now()->format('Y') - $year) * 42000 + 85000 + ($index * 3750));
    }

    private function summary(OperationMaintenance $maintenance): string
    {
        $labels = [
            'oil_change' => 'Cambio de aceite',
            'tire_change' => 'Cambio de neumaticos',
            'part_replacement' => 'Cambio de piezas',
        ];

        return ($labels[$maintenance->service_type] ?? 'Mantenimiento').': '.$maintenance->items()->pluck('name')->implode(', ').'.';
    }

    private function technician(int $index): string
    {
        return ['Ramon Castillo', 'Luis Paredes', 'Miguel Aquino', 'Carlos Mendez'][$index % 4];
    }

    private function tireBrand(int $index): string
    {
        return ['Michelin', 'Bridgestone', 'Goodyear', 'Linglong'][$index % 4];
    }

    private function partName(int $index): string
    {
        return ['Pastillas de freno', 'Valvula de frenos', 'Correa de motor', 'Manguera de intercooler', 'Bateria'][$index % 5];
    }

    private function partCost(string $partName): int
    {
        return [
            'Pastillas de freno' => 6800,
            'Valvula de frenos' => 3500,
            'Correa de motor' => 4200,
            'Manguera de intercooler' => 2500,
            'Bateria' => 12500,
        ][$partName] ?? 3000;
    }
}

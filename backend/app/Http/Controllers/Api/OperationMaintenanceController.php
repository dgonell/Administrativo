<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperationBus;
use App\Models\OperationBusHistory;
use App\Models\OperationMaintenance;
use App\Models\OperationPart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OperationMaintenanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            OperationMaintenance::query()
                ->with(['bus:id,fleet_number,brand,model', 'items'])
                ->when($request->integer('operation_bus_id'), fn ($query, int $busId) => $query->where('operation_bus_id', $busId))
                ->latest('service_date')
                ->latest('id')
                ->limit(200)
                ->get()
        );
    }

    public function catalogs(): JsonResponse
    {
        return response()->json([
            'parts' => OperationPart::query()->orderBy('category')->orderBy('name')->get(),
            'tire_positions' => $this->tirePositions(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'operation_bus_id' => ['required', 'integer', Rule::exists('operation_buses', 'id')],
            'service_type' => ['required', Rule::in(['oil_change', 'filter_change', 'tire_change', 'part_replacement', 'preventive', 'repair', 'other'])],
            'service_date' => ['required', 'date'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'workshop' => ['nullable', 'string', 'max:255'],
            'technician' => ['nullable', 'string', 'max:255'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'next_due_date' => ['nullable', 'date', 'after_or_equal:service_date'],
            'next_due_mileage' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.operation_part_id' => ['nullable', 'integer', Rule::exists('operation_parts', 'id')],
            'items.*.category' => ['required', Rule::in(['oil', 'filter', 'tire', 'part', 'supply'])],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.tire_position' => ['nullable', 'string', 'max:60', Rule::in($this->tirePositions())],
            'items.*.brand' => ['nullable', 'string', 'max:100'],
            'items.*.reference' => ['nullable', 'string', 'max:140'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($data['items'] as $index => $item) {
            if ($item['category'] === 'tire' && empty($item['tire_position'])) {
                return response()->json([
                    'message' => 'Debes indicar la posicion de cada neumatico.',
                    'errors' => ["items.{$index}.tire_position" => ['Selecciona la posicion del neumatico.']],
                ], 422);
            }

            if ($item['category'] === 'tire' && $item['quantity'] !== 1) {
                return response()->json([
                    'message' => 'Registra cada neumatico por separado para identificar su posicion.',
                    'errors' => ["items.{$index}.quantity" => ['La cantidad debe ser 1 para cada posicion.']],
                ], 422);
            }
        }

        $tirePositions = collect($data['items'])
            ->where('category', 'tire')
            ->pluck('tire_position')
            ->filter();

        if ($tirePositions->duplicates()->isNotEmpty()) {
            return response()->json([
                'message' => 'No puedes registrar dos neumaticos para la misma posicion en un servicio.',
                'errors' => ['items' => ['Revisa las posiciones seleccionadas.']],
            ], 422);
        }

        $bus = OperationBus::query()->findOrFail($data['operation_bus_id']);
        if (! empty($data['mileage']) && $bus->current_mileage && $data['mileage'] < $bus->current_mileage) {
            return response()->json(['message' => 'El kilometraje del mantenimiento no puede ser menor al kilometraje actual de la unidad.'], 422);
        }

        $baselineMileage = $data['mileage'] ?? $bus->current_mileage;
        if (! empty($data['next_due_mileage']) && $baselineMileage && $data['next_due_mileage'] < $baselineMileage) {
            return response()->json(['message' => 'El proximo kilometraje debe ser mayor o igual al kilometraje actual.'], 422);
        }

        $maintenance = DB::transaction(function () use ($request, $data, $bus) {
            $items = $data['items'];
            unset($data['items']);
            $maintenance = OperationMaintenance::query()->create($data + ['created_by' => $request->user()?->id]);
            $maintenance->items()->createMany($items);

            if (! empty($data['mileage']) && (! $bus->current_mileage || $data['mileage'] >= $bus->current_mileage)) {
                $bus->update(['current_mileage' => $data['mileage'], 'mileage_updated_at' => $data['service_date']]);
            }

            OperationBusHistory::query()->create([
                'operation_bus_id' => $bus->id,
                'fleet_number' => $bus->fleet_number,
                'action' => 'Mantenimiento registrado',
                'detail' => $this->summary($maintenance),
                'user_id' => $request->user()?->id,
            ]);

            return $maintenance;
        });

        return response()->json($maintenance->load(['bus:id,fleet_number,brand,model', 'items']), 201);
    }

    private function summary(OperationMaintenance $maintenance): string
    {
        $types = [
            'oil_change' => 'Cambio de aceite',
            'filter_change' => 'Cambio de filtros',
            'tire_change' => 'Cambio de neumaticos',
            'part_replacement' => 'Cambio de piezas',
            'preventive' => 'Mantenimiento preventivo',
            'repair' => 'Reparacion',
            'other' => 'Otro servicio',
        ];

        return ($types[$maintenance->service_type] ?? $maintenance->service_type).': '.$maintenance->items()->pluck('name')->implode(', ').'.';
    }

    private function tirePositions(): array
    {
        return [
            'Delantero izquierdo', 'Delantero derecho',
            'Trasero izquierdo exterior', 'Trasero izquierdo interior',
            'Trasero derecho exterior', 'Trasero derecho interior',
            'Repuesto',
        ];
    }
}

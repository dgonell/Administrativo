<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperationBus;
use App\Models\OperationBusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OperationBusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            OperationBus::query()
                ->with('driver:id,first_name,last_name,code')
                ->orderBy('fleet_number')
                ->get()
        );
    }

    public function history(): JsonResponse
    {
        return response()->json(
            OperationBusHistory::query()
                ->with('user:id,name')
                ->latest()
                ->limit(150)
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $bus = OperationBus::query()->create($this->validatedForCreate($request));
        $this->recordHistory($request, $bus, 'Registrado');

        return response()->json($bus->load('driver:id,first_name,last_name,code'), 201);
    }

    public function update(Request $request, OperationBus $operationBus): JsonResponse
    {
        $operationBus->update($this->validatedGeneral($request, $operationBus));
        $this->recordHistory($request, $operationBus, 'Ficha actualizada', 'Informacion general actualizada.');

        return response()->json($operationBus->load('driver:id,first_name,last_name,code'));
    }

    public function updateStatus(Request $request, OperationBus $operationBus): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['operational', 'workshop', 'inactive'])]]);
        $previousStatus = $operationBus->status;
        $operationBus->update($data);
        $this->recordHistory($request, $operationBus, 'Estado actualizado', "Estado: {$previousStatus} -> {$operationBus->status}.");

        return response()->json($operationBus->load('driver:id,first_name,last_name,code'));
    }

    public function updateMileage(Request $request, OperationBus $operationBus): JsonResponse
    {
        $data = $request->validate([
            'current_mileage' => ['required', 'integer', 'min:0'],
            'mileage_updated_at' => ['required', 'date'],
        ]);
        $operationBus->update($data);
        $this->recordHistory($request, $operationBus, 'Kilometraje actualizado', "Kilometraje: {$operationBus->current_mileage} km.");

        return response()->json($operationBus->load('driver:id,first_name,last_name,code'));
    }

    public function assignDriver(Request $request, OperationBus $operationBus): JsonResponse
    {
        $data = $request->validate(['driver_id' => ['nullable', 'integer', Rule::exists('drivers', 'id')]]);
        $operationBus->update($data);
        $this->recordHistory($request, $operationBus, 'Chofer actualizado', $operationBus->driver_id ? 'Se actualizo la asignacion de chofer.' : 'La unidad quedo sin chofer asignado.');

        return response()->json($operationBus->load('driver:id,first_name,last_name,code'));
    }

    public function destroy(Request $request, OperationBus $operationBus): JsonResponse
    {
        $operationBus->update(['status' => 'inactive']);
        $this->recordHistory($request, $operationBus, 'Desactivado', 'Unidad marcada como fuera de servicio.');

        return response()->json($operationBus->load('driver:id,first_name,last_name,code'));
    }

    public function uploadPhoto(Request $request, OperationBus $operationBus): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:3072'],
        ]);

        if ($operationBus->photo_path) {
            Storage::disk('public')->delete($operationBus->photo_path);
        }

        $path = $data['photo']->store('operations/buses', 'public');
        $operationBus->update(['photo_path' => $path]);
        $this->recordHistory($request, $operationBus, 'Foto actualizada', 'Se actualizo la imagen de la unidad.');

        return response()->json($operationBus->load('driver:id,first_name,last_name,code'));
    }

    private function validatedForCreate(Request $request): array
    {
        return $request->validate([
            ...$this->generalRules(),
            'fleet_number' => ['required', 'string', 'max:40', Rule::unique('operation_buses')],
            'plate' => ['required', 'string', 'max:30', Rule::unique('operation_buses')],
            'chassis' => ['nullable', 'string', 'max:100', Rule::unique('operation_buses')],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
            'mileage_updated_at' => ['nullable', 'date'],
            'driver_id' => ['nullable', 'integer', Rule::exists('drivers', 'id')],
            'status' => ['required', Rule::in(['operational', 'workshop', 'inactive'])],
        ]);
    }

    private function validatedGeneral(Request $request, OperationBus $bus): array
    {
        return $request->validate([
            ...$this->generalRules(),
            'fleet_number' => ['required', 'string', 'max:40', Rule::unique('operation_buses')->ignore($bus->id)],
            'plate' => ['required', 'string', 'max:30', Rule::unique('operation_buses')->ignore($bus->id)],
            'chassis' => ['nullable', 'string', 'max:100', Rule::unique('operation_buses')->ignore($bus->id)],
        ]);
    }

    private function generalRules(): array
    {
        return [
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:140'],
            'vehicle_type' => ['required', Rule::in(['bus', 'minibus', 'van', 'truck'])],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'color' => ['nullable', 'string', 'max:60'],
            'acquired_at' => ['nullable', 'date'],
            'insurer' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function recordHistory(Request $request, OperationBus $bus, string $action, ?string $detail = null): void
    {
        OperationBusHistory::query()->create([
            'operation_bus_id' => $bus->id,
            'fleet_number' => $bus->fleet_number,
            'action' => $action,
            'detail' => $detail ?? "{$bus->brand} {$bus->model} - placa {$bus->plate}",
            'user_id' => $request->user()?->id,
        ]);
    }

}

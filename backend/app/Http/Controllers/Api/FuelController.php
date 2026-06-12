<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelAdjustment;
use App\Models\FuelAlert;
use App\Models\FuelAuditLog;
use App\Models\FuelDailyClosure;
use App\Models\FuelDispatch;
use App\Models\FuelHose;
use App\Models\FuelPartner;
use App\Models\FuelPartnerVehicle;
use App\Models\FuelPurchase;
use App\Models\FuelTank;
use App\Models\FuelTankMeasurement;
use App\Models\FuelVehicleLimit;
use App\Models\Driver;
use App\Models\OperationBus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FuelController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $monthStart = now()->startOfMonth();
        $todayStart = now()->startOfDay();

        $postedDispatches = FuelDispatch::query()->where('status', 'posted');
        $monthDispatches = (clone $postedDispatches)->where('dispatched_at', '>=', $monthStart);
        $todayDispatches = (clone $postedDispatches)->where('dispatched_at', '>=', $todayStart);

        $vehicleRank = (clone $monthDispatches)
            ->selectRaw("COALESCE(operation_bus_id, fuel_partner_vehicle_id) as vehicle_id, recipient_type, SUM(liters) as liters, SUM(total_cost) as cost")
            ->groupBy('vehicle_id', 'recipient_type')
            ->orderByDesc('liters')
            ->limit(8)
            ->get();

        return response()->json([
            'tanks' => FuelTank::query()->with('hoses')->orderBy('name')->get(),
            'metrics' => [
                'stock_liters' => (float) FuelTank::query()->sum('current_liters'),
                'capacity_liters' => (float) FuelTank::query()->sum('capacity_liters'),
                'today_liters' => (float) $todayDispatches->sum('liters'),
                'month_liters' => (float) $monthDispatches->sum('liters'),
                'company_liters' => (float) (clone $monthDispatches)->where('recipient_type', 'company_bus')->sum('liters'),
                'partner_liters' => (float) (clone $monthDispatches)->where('recipient_type', 'partner_vehicle')->sum('liters'),
                'month_cost' => (float) (clone $monthDispatches)->sum('total_cost'),
                'open_alerts' => FuelAlert::query()->whereNull('resolved_at')->count(),
                'low_stock_tanks' => FuelTank::query()->whereColumn('current_liters', '<=', 'minimum_liters')->count(),
            ],
            'hose_totals' => (clone $monthDispatches)
                ->selectRaw('fuel_hose_id, SUM(liters) as liters, COUNT(*) as operations')
                ->with('hose:id,code,name')
                ->groupBy('fuel_hose_id')
                ->get(),
            'vehicle_rank' => $vehicleRank,
            'recent_alerts' => FuelAlert::query()->whereNull('resolved_at')->latest()->limit(10)->get(),
        ]);
    }

    public function catalogs(): JsonResponse
    {
        return response()->json([
            'tanks' => FuelTank::query()->with('hoses')->orderBy('name')->get(),
            'hoses' => FuelHose::query()->with('tank:id,name')->orderBy('code')->get(),
            'company_vehicles' => OperationBus::query()->select('id', 'fleet_number', 'brand', 'model', 'plate', 'current_mileage', 'capacity')->orderBy('fleet_number')->get(),
            'drivers' => Driver::query()->select('id', 'code', 'first_name', 'last_name')->orderBy('first_name')->orderBy('last_name')->get(),
            'partners' => FuelPartner::query()->with('vehicles')->orderBy('name')->get(),
            'recipient_types' => ['company_bus', 'partner_vehicle', 'authorized_external'],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type', 'dispatches');

        return response()->json(match ($type) {
            'purchases' => FuelPurchase::query()->with('tank:id,name')->latest('received_at')->limit(200)->get(),
            'measurements' => FuelTankMeasurement::query()->latest('measured_at')->limit(200)->get(),
            'closures' => FuelDailyClosure::query()->with('hose:id,code,name')->latest('closed_on')->limit(200)->get(),
            'adjustments' => FuelAdjustment::query()->latest('adjusted_at')->limit(200)->get(),
            'partners' => FuelPartner::query()->with('vehicles')->orderBy('name')->get(),
            'alerts' => FuelAlert::query()->latest()->limit(100)->get(),
            default => FuelDispatch::query()
                ->with(['tank:id,name', 'hose:id,code,name', 'bus:id,fleet_number,plate,brand,model', 'partner:id,name', 'partnerVehicle:id,plate,brand,model'])
                ->latest('dispatched_at')
                ->limit(250)
                ->get(),
        });
    }

    public function storeTank(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('fuel_tanks')],
            'fuel_type' => ['required', Rule::in(['diesel', 'gasoline', 'gnv', 'other'])],
            'capacity_liters' => ['required', 'numeric', 'min:1'],
            'current_liters' => ['nullable', 'numeric', 'min:0'],
            'minimum_liters' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'maintenance', 'blocked'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tank = FuelTank::query()->create($data);
        $this->audit($request, 'fuel.tank.created', $tank, null, $tank->toArray());

        return response()->json($tank, 201);
    }

    public function storeHose(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fuel_tank_id' => ['required', 'integer', Rule::exists('fuel_tanks', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'current_counter' => ['nullable', 'numeric', 'min:0'],
            'allowed_difference_liters' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'maintenance', 'blocked'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $hose = DB::transaction(function () use ($request, $data) {
            $hose = FuelHose::query()->create($data + [
                'code' => 'PENDIENTE-'.uniqid(),
            ]);
            $hose->update(['code' => 'M-'.$hose->id]);
            $this->audit($request, 'fuel.hose.created', $hose, null, $hose->fresh()->toArray());

            return $hose->fresh();
        });

        return response()->json($hose->load('tank:id,name'), 201);
    }

    public function storePartner(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'monthly_quota_liters' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'vehicles' => ['array'],
            'vehicles.*.plate' => ['required_with:vehicles', 'string', 'max:30', Rule::unique('fuel_partner_vehicles', 'plate')],
            'vehicles.*.brand' => ['nullable', 'string', 'max:120'],
            'vehicles.*.model' => ['nullable', 'string', 'max:120'],
            'vehicles.*.tank_capacity_liters' => ['nullable', 'numeric', 'min:0'],
            'vehicles.*.expected_efficiency' => ['nullable', 'numeric', 'min:0'],
            'vehicles.*.monthly_quota_liters' => ['nullable', 'numeric', 'min:0'],
        ]);

        $partner = DB::transaction(function () use ($request, $data) {
            $vehicles = $data['vehicles'] ?? [];
            unset($data['vehicles']);
            $partner = FuelPartner::query()->create($data + ['is_active' => $data['is_active'] ?? true]);
            $partner->vehicles()->createMany($vehicles);
            $this->audit($request, 'fuel.partner.created', $partner, null, $partner->load('vehicles')->toArray());

            return $partner;
        });

        return response()->json($partner->load('vehicles'), 201);
    }

    public function updatePartner(Request $request, FuelPartner $fuelPartner): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'monthly_quota_liters' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $fuelPartner->toArray();
        $fuelPartner->update($data + ['is_active' => $data['is_active'] ?? $fuelPartner->is_active]);
        $this->audit($request, 'fuel.partner.updated', $fuelPartner, $before, $fuelPartner->fresh()->toArray());

        return response()->json($fuelPartner->fresh()->load('vehicles'));
    }

    public function storePurchase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fuel_tank_id' => ['required', 'integer', Rule::exists('fuel_tanks', 'id')],
            'received_at' => ['required', 'date'],
            'supplier' => ['required', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:120'],
            'liters' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'tank_liters_after' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $purchase = DB::transaction(function () use ($request, $data) {
            $tank = FuelTank::query()->lockForUpdate()->findOrFail($data['fuel_tank_id']);
            $before = (float) $tank->current_liters;
            $unitCost = (float) ($data['unit_cost'] ?? 0);
            $liters = (float) $data['liters'];
            $after = isset($data['tank_liters_after']) ? (float) $data['tank_liters_after'] : $before + $liters;

            if ($after > (float) $tank->capacity_liters) {
                abort(422, 'La recepcion excede la capacidad del tanque.');
            }

            $purchase = FuelPurchase::query()->create($data + [
                'unit_cost' => $unitCost,
                'total_cost' => round($liters * $unitCost, 2),
                'tank_liters_before' => $before,
                'tank_liters_after' => $after,
                'difference_liters' => round($after - ($before + $liters), 2),
                'created_by' => $request->user()?->id,
            ]);

            $tank->update(['current_liters' => $after]);
            $this->audit($request, 'fuel.purchase.created', $purchase, null, $purchase->toArray());
            $this->refreshStockAlert($tank);

            return $purchase;
        });

        return response()->json($purchase->load('tank:id,name'), 201);
    }

    public function updatePurchase(Request $request, FuelPurchase $fuelPurchase): JsonResponse
    {
        $data = $request->validate([
            'received_at' => ['required', 'date'],
            'supplier' => ['required', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:120'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $fuelPurchase->toArray();
        $unitCost = (float) ($data['unit_cost'] ?? 0);
        $fuelPurchase->update($data + [
            'unit_cost' => $unitCost,
            'total_cost' => round((float) $fuelPurchase->liters * $unitCost, 2),
        ]);
        $this->audit($request, 'fuel.purchase.updated', $fuelPurchase, $before, $fuelPurchase->fresh()->toArray());

        return response()->json($fuelPurchase->fresh()->load('tank:id,name'));
    }

    public function storeDispatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fuel_hose_id' => ['required', 'integer', Rule::exists('fuel_hoses', 'id')],
            'recipient_type' => ['required', Rule::in(['company_bus', 'partner_vehicle', 'authorized_external'])],
            'operation_bus_id' => ['nullable', 'integer', Rule::exists('operation_buses', 'id')],
            'fuel_partner_id' => ['nullable', 'integer', Rule::exists('fuel_partners', 'id')],
            'fuel_partner_vehicle_id' => ['nullable', 'integer', Rule::exists('fuel_partner_vehicles', 'id')],
            'dispatched_at' => ['required', 'date'],
            'hose_counter_start' => ['required', 'numeric', 'min:0'],
            'hose_counter_end' => ['required', 'numeric', 'min:0'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'hourmeter' => ['nullable', 'integer', 'min:0'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'operator_name' => ['nullable', 'string', 'max:255'],
            'authorized_by' => ['nullable', 'string', 'max:255'],
            'cost_center' => ['nullable', 'string', 'max:255'],
            'route_or_service' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['recipient_type'] === 'company_bus' && empty($data['operation_bus_id'])) {
            return response()->json(['message' => 'Selecciona la unidad de la empresa.'], 422);
        }

        if ($data['recipient_type'] === 'partner_vehicle' && (empty($data['fuel_partner_id']) || empty($data['fuel_partner_vehicle_id']))) {
            return response()->json(['message' => 'Selecciona el socio y su vehiculo autorizado.'], 422);
        }

        $dispatch = DB::transaction(function () use ($request, $data) {
            $hose = FuelHose::query()->with('tank')->lockForUpdate()->findOrFail($data['fuel_hose_id']);
            $tank = FuelTank::query()->lockForUpdate()->findOrFail($hose->fuel_tank_id);
            $start = (float) $data['hose_counter_start'];
            $end = (float) $data['hose_counter_end'];
            $liters = round($end - $start, 3);

            if ($hose->status !== 'active' || $tank->status !== 'active') {
                abort(422, 'El tanque o la manguera no estan activos.');
            }

            if ($end <= $start) {
                abort(422, 'La lectura final de la manguera debe ser mayor a la inicial.');
            }

            if ($start < (float) $hose->current_counter) {
                abort(422, 'La lectura inicial no puede ser menor al contador actual de la manguera.');
            }

            if ($liters > (float) $tank->current_liters) {
                abort(422, 'El tanque no tiene saldo suficiente para este despacho.');
            }

            $unitCost = $this->weightedUnitCost();
            $dispatch = FuelDispatch::query()->create($data + [
                'fuel_tank_id' => $tank->id,
                'liters' => $liters,
                'unit_cost' => $unitCost,
                'total_cost' => round($liters * $unitCost, 2),
                'status' => 'posted',
                'created_by' => $request->user()?->id,
            ]);

            $tank->update(['current_liters' => round((float) $tank->current_liters - $liters, 2)]);
            $hose->update(['current_counter' => $end]);
            $this->audit($request, 'fuel.dispatch.created', $dispatch, null, $dispatch->toArray());
            $this->detectDispatchAlerts($request, $dispatch);
            $this->refreshStockAlert($tank->fresh());

            return $dispatch;
        });

        return response()->json($dispatch->load(['tank:id,name', 'hose:id,code,name', 'bus:id,fleet_number,plate,brand,model', 'partner:id,name', 'partnerVehicle:id,plate,brand,model']), 201);
    }

    public function updateDispatch(Request $request, FuelDispatch $fuelDispatch): JsonResponse
    {
        $data = $request->validate([
            'odometer' => ['nullable', 'integer', 'min:0'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'operator_name' => ['nullable', 'string', 'max:255'],
            'authorized_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $fuelDispatch->toArray();
        $fuelDispatch->update($data);
        $this->audit($request, 'fuel.dispatch.updated', $fuelDispatch, $before, $fuelDispatch->fresh()->toArray());

        return response()->json($fuelDispatch->fresh()->load(['tank:id,name', 'hose:id,code,name', 'bus:id,fleet_number,plate,brand,model', 'partner:id,name', 'partnerVehicle:id,plate,brand,model']));
    }

    public function storeMeasurement(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fuel_tank_id' => ['required', 'integer', Rule::exists('fuel_tanks', 'id')],
            'measured_at' => ['required', 'date'],
            'physical_liters' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $measurement = FuelTankMeasurement::query()->create($data + [
            'theoretical_liters' => FuelTank::query()->findOrFail($data['fuel_tank_id'])->current_liters,
            'difference_liters' => round((float) $data['physical_liters'] - (float) FuelTank::query()->findOrFail($data['fuel_tank_id'])->current_liters, 2),
            'created_by' => $request->user()?->id,
        ]);

        if (abs((float) $measurement->difference_liters) > 5) {
            FuelAlert::query()->create([
                'type' => 'tank_difference',
                'severity' => 'critical',
                'title' => 'Diferencia fisica de tanque',
                'message' => "Diferencia detectada: {$measurement->difference_liters} litros.",
                'alertable_type' => FuelTankMeasurement::class,
                'alertable_id' => $measurement->id,
                'created_by' => $request->user()?->id,
            ]);
        }

        $this->audit($request, 'fuel.measurement.created', $measurement, null, $measurement->toArray());

        return response()->json($measurement, 201);
    }

    public function updateMeasurement(Request $request, FuelTankMeasurement $fuelTankMeasurement): JsonResponse
    {
        $data = $request->validate([
            'measured_at' => ['required', 'date'],
            'physical_liters' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $fuelTankMeasurement->toArray();
        $fuelTankMeasurement->update($data + [
            'difference_liters' => round((float) $data['physical_liters'] - (float) $fuelTankMeasurement->theoretical_liters, 2),
        ]);
        $this->audit($request, 'fuel.measurement.updated', $fuelTankMeasurement, $before, $fuelTankMeasurement->fresh()->toArray());

        return response()->json($fuelTankMeasurement->fresh());
    }

    public function storeClosure(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fuel_hose_id' => ['required', 'integer', Rule::exists('fuel_hoses', 'id')],
            'closed_on' => ['required', 'date'],
            'counter_start' => ['required', 'numeric', 'min:0'],
            'counter_end' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $closedOn = Carbon::parse($data['closed_on']);
        $systemLiters = FuelDispatch::query()
            ->where('fuel_hose_id', $data['fuel_hose_id'])
            ->where('status', 'posted')
            ->whereDate('dispatched_at', $closedOn->toDateString())
            ->sum('liters');

        $counterLiters = round((float) $data['counter_end'] - (float) $data['counter_start'], 3);
        if ($counterLiters < 0) {
            return response()->json(['message' => 'La lectura final del cierre debe ser mayor a la inicial.'], 422);
        }

        $closure = FuelDailyClosure::query()->updateOrCreate(
            ['fuel_hose_id' => $data['fuel_hose_id'], 'closed_on' => $closedOn->toDateString()],
            $data + [
                'system_liters' => $systemLiters,
                'counter_liters' => $counterLiters,
                'difference_liters' => round($counterLiters - (float) $systemLiters, 3),
                'created_by' => $request->user()?->id,
            ]
        );

        $this->audit($request, 'fuel.closure.saved', $closure, null, $closure->toArray());

        return response()->json($closure->load('hose:id,code,name'), 201);
    }

    public function updateClosure(Request $request, FuelDailyClosure $fuelDailyClosure): JsonResponse
    {
        $data = $request->validate([
            'counter_start' => ['required', 'numeric', 'min:0'],
            'counter_end' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $counterLiters = round((float) $data['counter_end'] - (float) $data['counter_start'], 3);
        if ($counterLiters < 0) {
            return response()->json(['message' => 'La lectura final del cierre debe ser mayor a la inicial.'], 422);
        }

        $before = $fuelDailyClosure->toArray();
        $fuelDailyClosure->update($data + [
            'counter_liters' => $counterLiters,
            'difference_liters' => round($counterLiters - (float) $fuelDailyClosure->system_liters, 3),
        ]);
        $this->audit($request, 'fuel.closure.updated', $fuelDailyClosure, $before, $fuelDailyClosure->fresh()->toArray());

        return response()->json($fuelDailyClosure->fresh()->load('hose:id,code,name'));
    }

    public function storeAdjustment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fuel_tank_id' => ['required', 'integer', Rule::exists('fuel_tanks', 'id')],
            'adjusted_at' => ['required', 'date'],
            'liters' => ['required', 'numeric'],
            'type' => ['required', Rule::in(['merma', 'fuga', 'calibracion', 'correccion', 'otro'])],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $adjustment = DB::transaction(function () use ($request, $data) {
            $tank = FuelTank::query()->lockForUpdate()->findOrFail($data['fuel_tank_id']);
            $next = (float) $tank->current_liters + (float) $data['liters'];
            if ($next < 0 || $next > (float) $tank->capacity_liters) {
                abort(422, 'El ajuste deja el tanque fuera de rango.');
            }
            $adjustment = FuelAdjustment::query()->create($data + ['created_by' => $request->user()?->id]);
            $tank->update(['current_liters' => round($next, 2)]);
            $this->audit($request, 'fuel.adjustment.created', $adjustment, null, $adjustment->toArray());

            return $adjustment;
        });

        return response()->json($adjustment, 201);
    }

    public function updateAdjustment(Request $request, FuelAdjustment $fuelAdjustment): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $fuelAdjustment->toArray();
        $fuelAdjustment->update($data);
        $this->audit($request, 'fuel.adjustment.updated', $fuelAdjustment, $before, $fuelAdjustment->fresh()->toArray());

        return response()->json($fuelAdjustment->fresh());
    }

    public function voidDispatch(Request $request, FuelDispatch $fuelDispatch): JsonResponse
    {
        $data = $request->validate(['void_reason' => ['required', 'string', 'max:255']]);

        if ($fuelDispatch->status !== 'posted') {
            return response()->json(['message' => 'Este despacho ya fue anulado.'], 422);
        }

        DB::transaction(function () use ($request, $fuelDispatch, $data) {
            $before = $fuelDispatch->toArray();
            $tank = FuelTank::query()->lockForUpdate()->findOrFail($fuelDispatch->fuel_tank_id);
            $tank->update(['current_liters' => round((float) $tank->current_liters + (float) $fuelDispatch->liters, 2)]);
            $fuelDispatch->update([
                'status' => 'void',
                'voided_by' => $request->user()?->id,
                'voided_at' => now(),
                'void_reason' => $data['void_reason'],
            ]);
            $this->audit($request, 'fuel.dispatch.voided', $fuelDispatch, $before, $fuelDispatch->fresh()->toArray());
        });

        return response()->json($fuelDispatch->fresh());
    }

    private function weightedUnitCost(): float
    {
        $purchases = FuelPurchase::query()->latest('received_at')->limit(10)->get();
        $liters = (float) $purchases->sum('liters');

        if ($liters <= 0) {
            return 0;
        }

        return round((float) $purchases->sum('total_cost') / $liters, 4);
    }

    private function refreshStockAlert(FuelTank $tank): void
    {
        if ((float) $tank->current_liters > (float) $tank->minimum_liters) {
            return;
        }

        FuelAlert::query()->firstOrCreate([
            'type' => 'low_stock',
            'alertable_type' => FuelTank::class,
            'alertable_id' => $tank->id,
            'resolved_at' => null,
        ], [
            'severity' => 'critical',
            'title' => 'Stock bajo de combustible',
            'message' => "El tanque {$tank->name} esta en {$tank->current_liters} litros.",
        ]);
    }

    private function detectDispatchAlerts(Request $request, FuelDispatch $dispatch): void
    {
        $vehicleCapacity = null;
        if ($dispatch->recipient_type === 'company_bus' && $dispatch->operation_bus_id) {
            $vehicleCapacity = FuelVehicleLimit::query()
                ->where('operation_bus_id', $dispatch->operation_bus_id)
                ->value('tank_capacity_liters');
        }
        if ($dispatch->recipient_type === 'partner_vehicle' && $dispatch->fuel_partner_vehicle_id) {
            $vehicleCapacity = FuelPartnerVehicle::query()->find($dispatch->fuel_partner_vehicle_id)?->tank_capacity_liters;
        }

        if ($vehicleCapacity && (float) $dispatch->liters > (float) $vehicleCapacity) {
            FuelAlert::query()->create([
                'type' => 'capacity_exceeded',
                'severity' => 'warning',
                'title' => 'Despacho supera capacidad',
                'message' => "Despacho de {$dispatch->liters} litros contra capacidad {$vehicleCapacity}.",
                'alertable_type' => FuelDispatch::class,
                'alertable_id' => $dispatch->id,
                'created_by' => $request->user()?->id,
            ]);
        }
    }

    private function audit(Request $request, string $action, object $model, ?array $before, ?array $after): void
    {
        FuelAuditLog::query()->create([
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => $model->id ?? null,
            'before' => $before,
            'after' => $after,
            'user_id' => $request->user()?->id,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelDispatch extends Model
{
    protected $fillable = [
        'fuel_tank_id', 'fuel_hose_id', 'operation_bus_id', 'fuel_partner_id', 'fuel_partner_vehicle_id',
        'recipient_type', 'dispatched_at', 'hose_counter_start', 'hose_counter_end', 'liters',
        'unit_cost', 'total_cost', 'odometer', 'hourmeter', 'driver_name', 'operator_name',
        'authorized_by', 'cost_center', 'route_or_service', 'reason', 'status', 'notes',
        'created_by', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'hose_counter_start' => 'decimal:3',
        'hose_counter_end' => 'decimal:3',
        'liters' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'odometer' => 'integer',
        'hourmeter' => 'integer',
        'voided_at' => 'datetime',
    ];

    public function tank(): BelongsTo { return $this->belongsTo(FuelTank::class, 'fuel_tank_id'); }
    public function hose(): BelongsTo { return $this->belongsTo(FuelHose::class, 'fuel_hose_id'); }
    public function bus(): BelongsTo { return $this->belongsTo(OperationBus::class, 'operation_bus_id'); }
    public function partner(): BelongsTo { return $this->belongsTo(FuelPartner::class, 'fuel_partner_id'); }
    public function partnerVehicle(): BelongsTo { return $this->belongsTo(FuelPartnerVehicle::class, 'fuel_partner_vehicle_id'); }
}

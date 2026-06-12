<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'first_name',
        'last_name',
        'identity_document',
        'tss_worker_code',
        'birth_date',
        'phone',
        'contact_name',
        'email',
        'address',
        'photo_path',
        'department_id',
        'position_id',
        'contract_type_id',
        'hire_date',
        'termination_date',
        'status',
        'rehire_status',
        'rehire_notes',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function license(): HasOne
    {
        return $this->hasOne(DriverLicense::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(DriverEmergencyContact::class);
    }

    public function medicalLeaves(): HasMany
    {
        return $this->hasMany(DriverMedicalLeave::class);
    }

    public function conductReports(): HasMany
    {
        return $this->hasMany(DriverConductReport::class);
    }

    public function terminationRecords(): HasMany
    {
        return $this->hasMany(DriverTerminationRecord::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DriverStatusHistory::class);
    }

    public function trafficFineChecks(): HasMany
    {
        return $this->hasMany(DriverTrafficFineCheck::class);
    }
}

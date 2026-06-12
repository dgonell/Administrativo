<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Driver;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverModuleController extends Controller
{
    public function catalogs(): JsonResponse
    {
        return response()->json([
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'positions' => Position::query()->where('is_active', true)->orderBy('name')->get(),
            'contract_types' => ContractType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeDocument(Request $request, Driver $driver): JsonResponse
    {
        $document = $driver->documents()->create($request->validate([
            'document_type' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'file_path' => ['required', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'status' => ['required', Rule::in(['pending', 'valid', 'expired', 'replaced'])],
            'notes' => ['nullable', 'string'],
        ]) + ['file_disk' => 'local']);

        return response()->json($document, 201);
    }

    public function storeEmergencyContact(Request $request, Driver $driver): JsonResponse
    {
        $contact = $driver->emergencyContacts()->create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'relationship' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:40'],
            'secondary_phone' => ['nullable', 'string', 'max:40'],
        ]));

        return response()->json($contact, 201);
    }

    public function storeMedicalLeave(Request $request, Driver $driver): JsonResponse
    {
        $leave = $driver->medicalLeaves()->create($request->validate([
            'leave_type' => ['required', 'string', 'max:80'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'reason' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file_path' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]));

        return response()->json($leave, 201);
    }

    public function storeConductReport(Request $request, Driver $driver): JsonResponse
    {
        $report = $driver->conductReports()->create($request->validate([
            'event_date' => ['required', 'date'],
            'type' => ['required', 'string', 'max:80'],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'description' => ['required', 'string'],
            'action_taken' => ['nullable', 'string'],
            'file_path' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['open', 'reviewed', 'closed'])],
        ]));

        return response()->json($report, 201);
    }

    public function storeTerminationRecord(Request $request, Driver $driver): JsonResponse
    {
        $data = $request->validate([
            'termination_date' => ['required', 'date'],
            'termination_type' => ['required', Rule::in(['resignation', 'dismissal'])],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rehire_status' => ['required', Rule::in(['yes', 'no', 'review'])],
            'rehire_reason' => ['nullable', 'string'],
            'file_path' => ['nullable', 'string', 'max:255'],
        ]);

        $record = $driver->terminationRecords()->create($data);
        $previousStatus = $driver->status;
        $driver->update([
            'status' => $data['termination_type'],
            'termination_date' => $data['termination_date'],
            'rehire_status' => $data['rehire_status'],
            'rehire_notes' => $data['rehire_reason'] ?? null,
        ]);
        $driver->statusHistories()->create([
            'previous_status' => $previousStatus,
            'new_status' => $data['termination_type'],
            'reason' => $data['reason'],
        ]);

        return response()->json($record, 201);
    }

    public function rehire(Request $request, Driver $driver): JsonResponse
    {
        $data = $request->validate([
            'hire_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'rehire_status' => ['required', Rule::in(['yes', 'review'])],
            'notes' => ['nullable', 'string'],
        ]);

        $previousStatus = $driver->status;
        $driver->update([
            'status' => 'active',
            'hire_date' => $data['hire_date'],
            'termination_date' => null,
            'rehire_status' => $data['rehire_status'],
            'rehire_notes' => $data['notes'] ?? null,
        ]);
        $driver->statusHistories()->create([
            'previous_status' => $previousStatus,
            'new_status' => 'active',
            'reason' => $data['reason'] ?? 'Recontratacion',
        ]);

        return response()->json($driver->load([
            'department',
            'position',
            'contractType',
            'license',
            'terminationRecords',
            'statusHistories',
            'trafficFineChecks',
        ]));
    }

    public function storeTrafficFineCheck(Request $request, Driver $driver): JsonResponse
    {
        $check = $driver->trafficFineChecks()->create($request->validate([
            'license_number' => ['nullable', 'string', 'max:50'],
            'vehicle_plate' => ['nullable', 'string', 'max:30'],
            'checked_at' => ['nullable', 'date'],
            'source' => ['required', 'string', 'max:80'],
            'result_status' => ['required', Rule::in(['not_checked', 'clear', 'with_fines', 'manual_review_required'])],
            'result_summary' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'file_path' => ['nullable', 'string', 'max:255'],
            'next_check_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]));

        return response()->json($check, 201);
    }
}

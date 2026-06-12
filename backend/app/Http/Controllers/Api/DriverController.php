<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drivers\StoreDriverRequest;
use App\Http\Requests\Drivers\UpdateDriverRequest;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $drivers = Driver::query()
            ->with(['department', 'position', 'contractType', 'license', 'terminationRecords', 'statusHistories'])
            ->withCount(['documents', 'medicalLeaves', 'conductReports', 'terminationRecords', 'trafficFineChecks'])
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('identity_document', 'like', "%{$search}%");
                });
            })
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($drivers);
    }

    public function store(StoreDriverRequest $request): JsonResponse
    {
        $data = $request->validated();
        $license = $data['license'] ?? null;
        unset($data['license']);

        $driver = Driver::create($data + ['code' => $this->temporaryDriverCode()]);
        $driver->update(['code' => $this->driverCode($driver->id)]);
        $driver->statusHistories()->create([
            'previous_status' => null,
            'new_status' => $driver->status,
            'reason' => 'Contratacion inicial',
        ]);

        if ($license && array_filter($license)) {
            $driver->license()->create($license + ['status' => 'active']);
        }

        return response()->json($driver->load(['department', 'position', 'contractType', 'license']), 201);
    }

    public function show(Driver $driver): JsonResponse
    {
        return response()->json($driver->load([
            'department',
            'position',
            'contractType',
            'license',
            'documents',
            'emergencyContacts',
            'medicalLeaves',
            'conductReports',
            'terminationRecords',
            'statusHistories',
            'trafficFineChecks',
        ]));
    }

    public function update(UpdateDriverRequest $request, Driver $driver): JsonResponse
    {
        $data = $request->validated();
        $license = $data['license'] ?? null;
        unset($data['license']);
        unset($data['code']);

        $driver->update($data);

        if ($license && array_filter($license)) {
            $driver->license()->updateOrCreate(['driver_id' => $driver->id], $license + ['status' => 'active']);
        }

        return response()->json($driver->load(['department', 'position', 'contractType', 'license']));
    }

    public function uploadPhoto(Request $request, Driver $driver): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        if ($driver->photo_path) {
            Storage::disk('public')->delete($driver->photo_path);
        }

        $path = $data['photo']->store('drivers/photos', 'public');
        $driver->update(['photo_path' => $path]);

        return response()->json($driver->load(['department', 'position', 'contractType', 'license']));
    }

    public function destroy(Driver $driver): JsonResponse
    {
        $driver->delete();

        return response()->json(status: 204);
    }

    private function driverCode(int $id): string
    {
        return 'CH'.$id;
    }

    private function temporaryDriverCode(): string
    {
        return 'PENDIENTE-'.bin2hex(random_bytes(8));
    }
}

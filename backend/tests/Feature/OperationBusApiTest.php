<?php

namespace Tests\Feature;

use App\Models\OperationBus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperationBusApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_manages_operation_buses_and_history(): void
    {
        $this->authenticate();

        $bus = $this->postJson('/api/operation-buses', [
            'fleet_number' => '200',
            'brand' => 'Scania',
            'model' => 'K410',
            'vehicle_type' => 'bus',
            'plate' => 'I200001',
            'chassis' => '9BSK4X200TEST0001',
            'year' => 2020,
            'capacity' => 55,
            'status' => 'operational',
            'notes' => 'Unidad para servicios empresariales.',
        ])
            ->assertCreated()
            ->assertJsonPath('fleet_number', '200')
            ->assertJsonPath('capacity', 55)
            ->json();

        $this->getJson('/api/operation-buses')
            ->assertOk()
            ->assertJsonCount(1);

        $this->putJson("/api/operation-buses/{$bus['id']}", [
            'fleet_number' => '200',
            'brand' => 'Scania',
            'model' => 'K410',
            'vehicle_type' => 'bus',
            'plate' => 'I200001',
            'chassis' => '9BSK4X200TEST0001',
            'year' => 2020,
            'capacity' => 55,
            'notes' => 'Revision preventiva.',
        ])
            ->assertOk()
            ->assertJsonPath('notes', 'Revision preventiva.');

        $this->patchJson("/api/operation-buses/{$bus['id']}/status", [
            'status' => 'workshop',
        ])->assertOk()->assertJsonPath('status', 'workshop');

        $this->getJson('/api/operation-buses/history')
            ->assertOk()
            ->assertJsonCount(3);

        $this->deleteJson("/api/operation-buses/{$bus['id']}")
            ->assertOk()
            ->assertJsonPath('status', 'inactive');

        $this->getJson('/api/operation-buses')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'inactive');

        $this->getJson('/api/operation-buses/history')
            ->assertOk()
            ->assertJsonCount(4);
    }

    public function test_it_uploads_an_operation_bus_photo(): void
    {
        Storage::fake('public');
        $this->authenticate();

        $bus = $this->postJson('/api/operation-buses', [
            'fleet_number' => '201',
            'brand' => 'Hyundai',
            'model' => 'Universe',
            'vehicle_type' => 'bus',
            'plate' => 'I200002',
            'status' => 'operational',
        ])->assertCreated()->json();

        $response = $this->postJson("/api/operation-buses/{$bus['id']}/photo", [
            'photo' => UploadedFile::fake()->createWithContent(
                'autobus.jpg',
                base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAAB//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/EH//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/EH//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/EH//2Q==')
            ),
        ])->assertOk();

        Storage::disk('public')->assertExists($response->json('photo_path'));
    }

    public function test_mileage_permission_does_not_allow_other_bus_changes(): void
    {
        $this->seed(\Database\Seeders\AccessControlSeeder::class);
        $bus = OperationBus::query()->create([
            'fleet_number' => '202',
            'brand' => 'KIA',
            'model' => 'Grandbird',
            'vehicle_type' => 'bus',
            'plate' => 'I200003',
            'status' => 'operational',
        ]);
        $role = Role::query()->create(['name' => 'Control kilometraje', 'slug' => 'control-kilometraje']);
        $role->permissions()->sync(Permission::query()->whereIn('slug', [
            'system.access',
            'operations.buses.view',
            'operations.buses.mileage.update',
        ])->pluck('id'));
        $user = User::query()->create([
            'name' => 'Control kilometraje',
            'email' => 'kilometraje@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);
        $token = $this->postJson('/api/auth/login', [
            'email' => 'kilometraje@example.com',
            'password' => 'Password123!',
        ])->json('token');
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->withHeaders($headers)->patchJson("/api/operation-buses/{$bus->id}/mileage", [
            'current_mileage' => 150000,
            'mileage_updated_at' => '2026-06-02',
        ])->assertOk()->assertJsonPath('current_mileage', 150000);

        $this->withHeaders($headers)->patchJson("/api/operation-buses/{$bus->id}/status", [
            'status' => 'workshop',
        ])->assertForbidden()->assertJsonPath('permission', 'operations.buses.status.update');

        $this->withHeaders($headers)->putJson("/api/operation-buses/{$bus->id}", [
            'fleet_number' => '202',
            'brand' => 'Scania',
            'model' => 'K410',
            'vehicle_type' => 'bus',
            'plate' => 'I200003',
        ])->assertForbidden()->assertJsonPath('permission', 'operations.buses.update');
    }
}

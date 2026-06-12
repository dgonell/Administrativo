<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_tanks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('fuel_type', 40)->default('diesel')->index();
            $table->decimal('capacity_liters', 12, 2);
            $table->decimal('current_liters', 12, 2)->default(0);
            $table->decimal('minimum_liters', 12, 2)->default(0);
            $table->string('location')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_hoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_tank_id')->constrained('fuel_tanks')->cascadeOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->decimal('current_counter', 14, 3)->default(0);
            $table->decimal('allowed_difference_liters', 8, 3)->default(0.5);
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('monthly_quota_liters', 10, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_partner_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_partner_id')->constrained('fuel_partners')->cascadeOnDelete();
            $table->string('plate', 30)->unique();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->decimal('tank_capacity_liters', 8, 2)->nullable();
            $table->decimal('expected_efficiency', 8, 3)->nullable();
            $table->decimal('monthly_quota_liters', 10, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_vehicle_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_bus_id')->unique()->constrained('operation_buses')->cascadeOnDelete();
            $table->decimal('tank_capacity_liters', 8, 2)->nullable();
            $table->decimal('expected_efficiency', 8, 3)->nullable();
            $table->decimal('daily_quota_liters', 10, 2)->nullable();
            $table->decimal('weekly_quota_liters', 10, 2)->nullable();
            $table->decimal('monthly_quota_liters', 10, 2)->nullable();
            $table->boolean('requires_authorization')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_tank_id')->constrained('fuel_tanks')->cascadeOnDelete();
            $table->date('received_at');
            $table->string('supplier');
            $table->string('invoice_number')->nullable();
            $table->decimal('liters', 12, 2);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->decimal('tank_liters_before', 12, 2)->nullable();
            $table->decimal('tank_liters_after', 12, 2)->nullable();
            $table->decimal('difference_liters', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fuel_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_tank_id')->constrained('fuel_tanks')->cascadeOnDelete();
            $table->foreignId('fuel_hose_id')->constrained('fuel_hoses')->cascadeOnDelete();
            $table->foreignId('operation_bus_id')->nullable()->constrained('operation_buses')->nullOnDelete();
            $table->foreignId('fuel_partner_id')->nullable()->constrained('fuel_partners')->nullOnDelete();
            $table->foreignId('fuel_partner_vehicle_id')->nullable()->constrained('fuel_partner_vehicles')->nullOnDelete();
            $table->string('recipient_type', 40)->index();
            $table->dateTime('dispatched_at')->index();
            $table->decimal('hose_counter_start', 14, 3);
            $table->decimal('hose_counter_end', 14, 3);
            $table->decimal('liters', 10, 3);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->unsignedBigInteger('odometer')->nullable();
            $table->unsignedBigInteger('hourmeter')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('operator_name')->nullable();
            $table->string('authorized_by')->nullable();
            $table->string('cost_center')->nullable();
            $table->string('route_or_service')->nullable();
            $table->string('reason')->nullable();
            $table->string('status', 30)->default('posted')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_tank_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_tank_id')->constrained('fuel_tanks')->cascadeOnDelete();
            $table->dateTime('measured_at')->index();
            $table->decimal('theoretical_liters', 12, 2);
            $table->decimal('physical_liters', 12, 2);
            $table->decimal('difference_liters', 10, 2)->default(0);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fuel_daily_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_hose_id')->constrained('fuel_hoses')->cascadeOnDelete();
            $table->date('closed_on')->index();
            $table->decimal('counter_start', 14, 3);
            $table->decimal('counter_end', 14, 3);
            $table->decimal('system_liters', 10, 3)->default(0);
            $table->decimal('counter_liters', 10, 3)->default(0);
            $table->decimal('difference_liters', 10, 3)->default(0);
            $table->string('status', 30)->default('closed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['fuel_hose_id', 'closed_on']);
        });

        Schema::create('fuel_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_tank_id')->constrained('fuel_tanks')->cascadeOnDelete();
            $table->dateTime('adjusted_at')->index();
            $table->decimal('liters', 10, 2);
            $table->string('type', 40)->index();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fuel_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 60)->index();
            $table->string('severity', 30)->default('warning')->index();
            $table->string('title');
            $table->text('message');
            $table->nullableMorphs('alertable');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fuel_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 80)->index();
            $table->nullableMorphs('auditable');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('detail')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_audit_logs');
        Schema::dropIfExists('fuel_alerts');
        Schema::dropIfExists('fuel_adjustments');
        Schema::dropIfExists('fuel_daily_closures');
        Schema::dropIfExists('fuel_tank_measurements');
        Schema::dropIfExists('fuel_dispatches');
        Schema::dropIfExists('fuel_purchases');
        Schema::dropIfExists('fuel_vehicle_limits');
        Schema::dropIfExists('fuel_partner_vehicles');
        Schema::dropIfExists('fuel_partners');
        Schema::dropIfExists('fuel_hoses');
        Schema::dropIfExists('fuel_tanks');
    }
};

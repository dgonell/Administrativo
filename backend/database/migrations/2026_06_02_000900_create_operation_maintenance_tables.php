<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_parts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category', 40)->default('part')->index();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('operation_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_bus_id')->constrained('operation_buses')->cascadeOnDelete();
            $table->string('service_type', 40)->index();
            $table->date('service_date');
            $table->unsignedBigInteger('mileage')->nullable();
            $table->string('workshop')->nullable();
            $table->string('technician')->nullable();
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->date('next_due_date')->nullable();
            $table->unsignedBigInteger('next_due_mileage')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('operation_maintenance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_maintenance_id')->constrained('operation_maintenances')->cascadeOnDelete();
            $table->foreignId('operation_part_id')->nullable()->constrained('operation_parts')->nullOnDelete();
            $table->string('category', 40)->index();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('tire_position', 60)->nullable();
            $table->string('brand')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_maintenance_items');
        Schema::dropIfExists('operation_maintenances');
        Schema::dropIfExists('operation_parts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_buses', function (Blueprint $table) {
            $table->id();
            $table->string('fleet_number', 40)->unique();
            $table->string('brand', 100);
            $table->string('model', 140);
            $table->string('plate', 30)->unique();
            $table->string('chassis', 100)->nullable()->unique();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status', 30)->default('operational')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('operation_bus_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operation_bus_id')->nullable()->index();
            $table->string('fleet_number', 40)->index();
            $table->string('action', 40);
            $table->text('detail')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_bus_histories');
        Schema::dropIfExists('operation_buses');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operation_buses', function (Blueprint $table) {
            $table->string('vehicle_type', 40)->default('bus')->after('model');
            $table->string('color', 60)->nullable()->after('capacity');
            $table->unsignedBigInteger('current_mileage')->nullable()->after('color');
            $table->date('mileage_updated_at')->nullable()->after('current_mileage');
            $table->date('acquired_at')->nullable()->after('mileage_updated_at');
            $table->string('insurer')->nullable()->after('acquired_at');
            $table->string('photo_path')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('operation_buses', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_type',
                'color',
                'current_mileage',
                'mileage_updated_at',
                'acquired_at',
                'insurer',
                'photo_path',
            ]);
        });
    }
};

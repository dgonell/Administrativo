<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('tss_worker_code', 40)->nullable()->unique()->after('identity_document');
            $table->string('contact_name', 120)->nullable()->after('phone');
        });

        DB::table('drivers')
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy('id')
            ->each(function ($driver) {
                DB::table('drivers')
                    ->where('id', $driver->id)
                    ->update([
                        'tss_worker_code' => 'TSS-'.str_pad((string) $driver->id, 6, '0', STR_PAD_LEFT),
                        'contact_name' => $driver->first_name.' '.$driver->last_name,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropUnique(['tss_worker_code']);
            $table->dropColumn(['tss_worker_code', 'contact_name']);
        });
    }
};

<?php

use App\Models\Driver;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Driver::withTrashed()
            ->orderBy('id')
            ->get(['id', 'code'])
            ->each(function (Driver $driver): void {
                $driver->forceFill(['code' => 'CH'.$driver->id])->saveQuietly();
            });
    }

    public function down(): void
    {
        //
    }
};

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->call(AccessControlSeeder::class);

            return;
        }

        $this->call(DriverCatalogSeeder::class);
        $this->call(AccessControlSeeder::class);
        $this->call(OperationPartSeeder::class);
        $this->call(OperationBusImportSeeder::class);
        $this->call(OperationMaintenanceSeeder::class);
    }
}

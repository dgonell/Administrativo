<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'driver_traffic_fine_checks',
            'driver_status_histories',
            'driver_termination_records',
            'driver_conduct_reports',
            'driver_medical_leaves',
            'driver_emergency_contacts',
            'driver_documents',
            'driver_required_documents',
            'driver_licenses',
            'drivers',
            'departments',
            'positions',
            'contract_types',
            'finance_quote_lines',
            'finance_quotes',
            'finance_histories',
            'finance_routes',
            'finance_clients',
            'operation_maintenance_items',
            'operation_maintenances',
            'operation_bus_histories',
            'operation_buses',
            'operation_parts',
            'fuel_audit_logs',
            'fuel_alerts',
            'fuel_adjustments',
            'fuel_daily_closures',
            'fuel_tank_measurements',
            'fuel_dispatches',
            'fuel_purchases',
            'fuel_vehicle_limits',
            'fuel_partner_vehicles',
            'fuel_partners',
            'fuel_hoses',
            'fuel_tanks',
            'user_access_tokens',
            'user_permission_overrides',
            'sessions',
            'password_reset_tokens',
            'jobs',
            'job_batches',
            'failed_jobs',
            'cache',
            'cache_locks',
        ];

        Schema::disableForeignKeyConstraints();
        $this->withoutMySqlForeignKeyChecks(function () use ($tables): void {
            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $this->clearTable($table);
            }
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Data reset is intentionally irreversible.
    }

    private function withoutMySqlForeignKeyChecks(callable $callback): void
    {
        $isMySql = DB::getDriverName() === 'mysql';

        if ($isMySql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            $callback();
        } finally {
            if ($isMySql) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }

    private function clearTable(string $table): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::table($table)->delete();

            return;
        }

        DB::table($table)->truncate();
    }
};

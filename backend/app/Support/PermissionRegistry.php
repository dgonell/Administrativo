<?php

namespace App\Support;

class PermissionRegistry
{
    public static function groups(): array
    {
        return [
            'Sistema' => [
                'system.access' => 'Acceder al sistema',
                'users.view' => 'Ver usuarios',
                'users.manage' => 'Crear, editar y desactivar usuarios',
                'roles.manage' => 'Editar roles y permisos',
            ],
            'Choferes' => [
                'drivers.view' => 'Ver choferes',
                'drivers.create' => 'Registrar choferes',
                'drivers.update' => 'Editar choferes',
                'drivers.delete' => 'Eliminar choferes',
                'drivers.photo' => 'Subir foto del chofer',
                'drivers.documents.manage' => 'Gestionar documentos',
                'drivers.leaves.manage' => 'Gestionar permisos',
                'drivers.conduct.manage' => 'Gestionar conducta',
                'drivers.termination.manage' => 'Gestionar salidas y recontrataciones',
            ],
            'Finanzas' => [
                'finance.quotes.view' => 'Ver cotizaciones',
                'finance.quotes.manage' => 'Crear y editar cotizaciones',
                'finance.quotes.print' => 'Imprimir o descargar cotizaciones',
                'finance.clients.view' => 'Ver clientes',
                'finance.clients.manage' => 'Crear, editar y eliminar clientes',
                'finance.routes.view' => 'Ver rutas',
                'finance.routes.manage' => 'Crear, editar y eliminar rutas',
            ],
            'Operaciones' => [
                'operations.buses.view' => 'Ver autobuses',
                'operations.buses.history.view' => 'Ver historial de autobuses',
                'operations.buses.create' => 'Registrar autobuses',
                'operations.buses.update' => 'Editar ficha general del autobus',
                'operations.buses.photo' => 'Subir foto del autobus',
                'operations.buses.mileage.update' => 'Actualizar kilometraje',
                'operations.buses.driver.assign' => 'Asignar o cambiar chofer',
                'operations.buses.status.update' => 'Cambiar estado operativo',
                'operations.buses.retire' => 'Retirar autobus de servicio',
                'operations.maintenance.view' => 'Ver mantenimientos de autobuses',
                'operations.maintenance.create' => 'Registrar mantenimientos y cambios de piezas',
            ],
            'Combustible' => [
                'fuel.view' => 'Ver modulo de combustible',
                'fuel.settings.manage' => 'Gestionar tanques y mangueras',
                'fuel.purchases.manage' => 'Registrar recepciones de combustible',
                'fuel.dispatches.manage' => 'Registrar despachos de combustible',
                'fuel.dispatches.void' => 'Anular despachos de combustible',
                'fuel.measurements.manage' => 'Registrar mediciones fisicas del tanque',
                'fuel.closures.manage' => 'Registrar cierres diarios de mangueras',
                'fuel.adjustments.manage' => 'Registrar ajustes de inventario',
                'fuel.partners.manage' => 'Gestionar socios y vehiculos autorizados',
            ],
        ];
    }

    public static function all(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $permissions) => $permissions)
            ->all();
    }
}

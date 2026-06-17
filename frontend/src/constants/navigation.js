export const APP_VERSION = 'v1.0.0'

export const APP_MODULES = {
  drivers: 'Choferes',
  finance: 'Cotizar',
  buses: 'Autobuses',
  fuel: 'Combustible',
  users: 'Usuarios',
}

export const FINANCE_MODULES = {
  quote: APP_MODULES.finance,
  history: 'Historial',
  clients: 'Clientes',
  routes: 'Rutas',
}

export const DRIVER_MODULES = {
  list: APP_MODULES.drivers,
  create: 'Nuevo chofer',
  reports: 'Reporte de choferes',
  file: 'Expediente',
  license: 'Licencia',
  documents: 'Documentos',
  leaves: 'Permisos',
  conduct: 'Conducta',
  termination: 'Salida',
}

export const OPERATIONS_MODULES = {
  buses: APP_MODULES.buses,
  fuel: APP_MODULES.fuel,
}


export const DRIVER_MENU_ITEMS = [
  DRIVER_MODULES.list,
]

export const FINANCE_MENU_GROUPS = [
  {
    label: 'Cotizaciones',
    items: [FINANCE_MODULES.quote, FINANCE_MODULES.history],
  },
  {
    label: 'Registros',
    items: [FINANCE_MODULES.clients, FINANCE_MODULES.routes],
  },
]

# Proyecto Administrativo

Web app administrativa para gestionar choferes, empleados, documentacion laboral, mecanica de autobuses, facturas, gastos, almacenes, gasoil y otros procesos operativos.

La prioridad inicial es construir el modulo de choferes con una base solida para crecer hacia empleados generales, mecanica, almacenes, gasoil, facturacion y nomina en fases posteriores.

## Stack Propuesto

- Backend: Laravel 11 o version LTS disponible al iniciar el desarrollo.
- Frontend: Vue 3 con Composition API.
- Integracion Laravel/Vue: Inertia.js o API REST con frontend separado.
- UI: Tailwind CSS, componentes propios y/o Headless UI.
- Base de datos: PostgreSQL recomendado; MySQL tambien es valido si el hosting lo exige.
- Autenticacion: Laravel Breeze, Jetstream o Fortify.
- Permisos: `spatie/laravel-permission`.
- Archivos/documentos: Laravel Filesystem, almacenamiento local al inicio y opcion futura S3 compatible.
- Reportes: PDF con DomPDF/Snappy y exportacion Excel con Laravel Excel.
- Auditoria: logs de actividad con `spatie/laravel-activitylog`.

## Arquitectura General

La aplicacion puede organizarse como un monolito modular en Laravel. Esto permite avanzar rapido sin perder orden.

```text
app/
  Modules/
    Drivers/
      Models/
      Controllers/
      Requests/
      Services/
      Actions/
      Data/
      Policies/
    Payroll/
    Employees/
    FleetMaintenance/
    Inventory/
    Fuel/
    Billing/
  Models/
  Policies/
  Services/
database/
  migrations/
  seeders/
resources/
  js/
    Pages/
      Drivers/
      Dashboard/
      Auth/
    Components/
    Layouts/
routes/
  web.php
  api.php
```

Cada modulo debe concentrar su logica principal: modelos, validaciones, servicios, politicas, acciones y pruebas. Las piezas compartidas deben vivir fuera de los modulos.

## Alcance del Primer Desarrollo: Choferes

El primer desarrollo se enfocara en el expediente completo del chofer. No se incluira nomina en esta fase.

Objetivo: tener un control confiable de choferes activos, suspendidos, retirados o no recontratables, con sus datos personales, licencia, documentos, permisos, reportes internos y salida laboral.

Funciones principales:

- Registro de datos personales del chofer.
- Registro de cedula o documento de identidad.
- Registro completo de licencia de conducir.
- Control de categoria de licencia.
- Fecha de emision y expiracion de licencia.
- Alertas de licencia vencida o por vencer.
- Carga ilimitada de documentos por chofer.
- Catalogo de documentos requeridos inicialmente.
- Seguimiento de documentos pendientes, vigentes y vencidos.
- Registro de permisos medicos y otros permisos.
- Registro de reportes de mala conducta o incidentes.
- Reportes PDF o Excel del expediente del chofer.
- Cambio de estado laboral.
- Registro de despido o desvinculacion.
- Evaluacion de recontratacion: apto, no apto o revisar.
- Historial de cambios relevantes.
- Auditoria de acciones importantes.

### Consulta de Multas de Transito

Se debe investigar formalmente con la entidad correspondiente antes de comprometer una integracion automatica.

Hallazgo inicial:

- En Bolivia existen servicios publicos relacionados con infracciones, ITV y pagos QR mediante RUAT y Policia Boliviana.
- La consulta publica encontrada esta orientada a infracciones por vehiculo/placa, no a una API publica documentada para consultar multas por numero de licencia.
- SEGIP publica servicios de licencias y sistemas internos, pero no se encontro una API publica abierta para terceros.

Decision tecnica inicial:

- No bloquear el modulo de choferes por esta integracion.
- Crear una tabla para registrar consultas manuales o futuras integraciones.
- Permitir cargar evidencias o comprobantes de multas.
- Dejar un servicio Laravel desacoplado llamado `TrafficFineLookupService` para conectar una API oficial si luego se obtiene acceso.
- Evitar scraping de portales publicos si no existe autorizacion expresa, porque puede romperse, violar terminos de uso o exponer datos sensibles.

Flujo inicial recomendado:

```text
Chofer
  -> Licencia
  -> Consulta manual de multas
  -> Registro de resultado
  -> Archivo adjunto opcional
  -> Proxima fecha de verificacion
```

## Modulos Iniciales

### 1. Usuarios, Roles y Permisos

Objetivo: controlar quien puede ver, crear, editar, aprobar, eliminar o exportar informacion.

Roles iniciales sugeridos:

- Super Admin: acceso total.
- Administrador: gestiona configuracion, usuarios y modulos principales.
- Recursos Humanos: gestiona choferes, empleados, documentos y reportes laborales.
- Operaciones: consulta choferes, licencias, permisos e incidentes.
- Contabilidad: gestiona facturas, gastos y reportes financieros.
- Almacen: gestiona entradas, salidas, inventario y proveedores.
- Mecanica: gestiona autobuses, reparaciones, repuestos y mantenimiento.
- Consulta: solo lectura segun permisos asignados.

Permisos iniciales para choferes:

- `drivers.view`
- `drivers.create`
- `drivers.update`
- `drivers.delete`
- `drivers.documents.view`
- `drivers.documents.upload`
- `drivers.documents.delete`
- `drivers.licenses.view`
- `drivers.licenses.manage`
- `drivers.medical_leaves.view`
- `drivers.medical_leaves.manage`
- `drivers.conduct_reports.view`
- `drivers.conduct_reports.manage`
- `drivers.termination.manage`
- `drivers.rehire_review.manage`
- `drivers.export`

### 2. Choferes

Objetivo: expediente completo del chofer.

Funciones iniciales:

- Registro de datos personales.
- Datos laborales basicos.
- Estado del chofer: activo, inactivo, suspendido, retirado, despedido.
- Datos de licencia de conducir.
- Historial de cambios importantes.
- Documentos adjuntos.
- Contactos de emergencia.
- Vista de expediente.
- Busqueda y filtros.
- Exportacion basica.

Campos sugeridos:

- Codigo interno.
- Nombres.
- Apellidos.
- Documento de identidad.
- Fecha de nacimiento.
- Telefono.
- Correo.
- Direccion.
- Fecha de ingreso.
- Cargo.
- Departamento/area.
- Tipo de contrato.
- Categoria de licencia.
- Numero de licencia.
- Fecha de emision de licencia.
- Fecha de expiracion de licencia.
- Restricciones u observaciones de licencia.
- Estado.
- Apto para recontratacion.
- Foto.

Tablas sugeridas:

```text
drivers
driver_licenses
driver_documents
driver_required_documents
driver_emergency_contacts
driver_medical_leaves
driver_conduct_reports
driver_termination_records
driver_traffic_fine_checks
driver_status_histories
departments
positions
contract_types
```

### 3. Documentacion de Choferes

Objetivo: organizar archivos por tipo y fecha de vencimiento.

Tipos de documentos sugeridos:

- Cedula o identificacion.
- Contrato.
- Licencia de conducir.
- Certificado medico.
- Antecedentes.
- Certificado de buena conducta.
- Hoja de vida.
- Referencias laborales.
- Prueba de domicilio.
- Comprobantes.
- Otros.

Campos sugeridos:

- Chofer.
- Tipo de documento.
- Nombre visible.
- Archivo.
- Fecha de emision.
- Fecha de vencimiento.
- Estado: vigente, vencido, pendiente, reemplazado.
- Observaciones.
- Usuario que subio el archivo.

Debe existir alerta de documentos vencidos o por vencer.

### 4. Permisos Medicos y Otros

Objetivo: controlar ausencias justificadas, permisos medicos, permisos personales y otros eventos.

Campos sugeridos:

- Chofer.
- Tipo de permiso.
- Fecha de inicio.
- Fecha de fin.
- Motivo.
- Diagnostico o descripcion, si aplica.
- Documento de respaldo.
- Estado: pendiente, aprobado, rechazado.
- Usuario que registra.
- Usuario que aprueba.

### 5. Reportes de Conducta e Incidentes

Objetivo: registrar eventos internos que afecten el historial del chofer.

Tipos sugeridos:

- Mala conducta.
- Queja de pasajero.
- Accidente.
- Incumplimiento de ruta.
- Incumplimiento de horario.
- Danio a unidad.
- Uso indebido de combustible.
- Otro.

Campos sugeridos:

- Chofer.
- Fecha del evento.
- Tipo.
- Gravedad: baja, media, alta, critica.
- Descripcion.
- Accion tomada.
- Documento o evidencia adjunta.
- Usuario que registra.
- Estado: abierto, revisado, cerrado.

### 6. Desvinculacion y Recontratacion

Objetivo: registrar por que sale un chofer y si puede ser considerado de nuevo.

Campos sugeridos:

- Fecha de salida.
- Tipo de salida: renuncia, despido, fin de contrato, abandono, otro.
- Razon principal.
- Descripcion detallada.
- Liquidacion/documentos adjuntos, si aplica.
- Apto para recontratacion: si, no, revisar.
- Motivo de la decision de recontratacion.
- Usuario que registra.
- Usuario que aprueba o valida.

### 7. Nomina

Nomina queda fuera del primer desarrollo. Se mantiene como modulo futuro.

Entidades futuras:

```text
payroll_periods
payroll_items
payroll_concepts
employee_payroll_records
employee_loans
employee_loan_payments
```

## Modulos Futuros

### Mecanica de Autobuses

- Autobuses.
- Kilometraje.
- Ordenes de trabajo.
- Mantenimientos preventivos y correctivos.
- Facturas de reparacion.
- Repuestos usados.
- Talleres/proveedores.
- Historial por autobus.

### Almacenes e Inventario

- Productos/repuestos.
- Categorias.
- Almacenes.
- Entradas.
- Salidas.
- Ajustes.
- Stock minimo.
- Transferencias.
- Kardex.

### Facturas y Gastos

- Proveedores.
- Facturas.
- Gastos operativos.
- Impuestos.
- Adjuntos.
- Estados de pago.
- Reportes por rango de fecha, proveedor y modulo.

### Gasoil

- Registro de cargas.
- Vehiculo/autobus.
- Chofer.
- Litros.
- Costo.
- Kilometraje.
- Rendimiento.
- Estacion/proveedor.
- Reportes de consumo.

## Modelo de Navegacion Inicial

```text
Dashboard
Choferes
  Listado
  Nuevo chofer
  Expediente
  Licencia
  Documentos
  Permisos
  Conducta
  Desvinculacion
Configuracion
  Usuarios
  Roles y permisos
  Departamentos
  Cargos
  Tipos de contrato
Reportes
```

## Disenio Frontend

El sistema debe sentirse como una herramienta de trabajo: limpio, responsive, rapido y facil de escanear.

Principios:

- Layout con sidebar en escritorio y menu compacto en movil.
- Tablas con filtros, busqueda y acciones claras.
- Formularios por secciones, no formularios interminables.
- Expediente del chofer con pestanas: resumen, licencia, documentos, permisos, conducta, salida laboral, historial.
- Estados visuales discretos: activo, inactivo, vencido, pendiente.
- Componentes reutilizables para botones, inputs, modales, tablas, badges y archivos.
- Validacion visible cerca del campo.
- Confirmaciones para eliminar o cambiar estados importantes.

Componentes Vue sugeridos:

```text
resources/js/Components/
  AppButton.vue
  AppInput.vue
  AppSelect.vue
  AppModal.vue
  DataTable.vue
  StatusBadge.vue
  FileUploader.vue
  ConfirmDialog.vue
```

## Seguridad

- Autenticacion obligatoria.
- Permisos por rol y por accion.
- Policies en Laravel para proteger cada recurso.
- Validacion con Form Requests.
- Proteccion CSRF.
- Sanitizacion de archivos subidos.
- Limite de tamano y tipos permitidos para documentos.
- Auditoria de acciones importantes.
- Soft deletes en entidades sensibles.
- Backups automaticos de base de datos y archivos.

## Convenciones Tecnicas

- Controladores delgados.
- Validaciones en `FormRequest`.
- Logica de negocio en `Services` o `Actions`.
- Permisos en `Policies`.
- Migraciones pequenas y claras.
- Seeders para roles, permisos y catalogos base.
- Tests para reglas criticas.
- Nombres de tablas en ingles y UI en espanol.

Ejemplo de flujo para crear chofer:

```text
DriverController@store
  -> StoreDriverRequest
  -> CreateDriverAction
  -> Driver model
  -> Activity log
  -> Redirect to driver profile
```

## Primera Fase de Desarrollo

1. Crear proyecto Laravel.
2. Instalar Vue, Inertia y Tailwind.
3. Configurar autenticacion.
4. Instalar roles y permisos.
5. Crear dashboard base.
6. Crear migraciones de choferes:
   - departments
   - positions
   - contract_types
   - drivers
   - driver_licenses
   - driver_required_documents
   - driver_documents
   - driver_emergency_contacts
   - driver_medical_leaves
   - driver_conduct_reports
   - driver_termination_records
   - driver_traffic_fine_checks
   - driver_status_histories
7. Crear CRUD de choferes.
8. Crear expediente del chofer.
9. Crear subida y gestion de documentos.
10. Crear alertas de documentos y licencias vencidas.
11. Crear usuarios, roles y permisos desde UI.
12. Crear reportes/exportaciones basicas.
13. Crear permisos medicos y otros permisos.
14. Crear reportes de conducta.
15. Crear flujo de desvinculacion y recontratacion.

## Estructura de Base de Datos Inicial

### drivers

```text
id
code
first_name
last_name
identity_document
birth_date
phone
email
address
photo_path
department_id
position_id
contract_type_id
hire_date
termination_date
status
rehire_status
rehire_notes
notes
created_by
updated_by
created_at
updated_at
deleted_at
```

### driver_licenses

```text
id
driver_id
license_number
category
issued_at
expires_at
issuing_entity
restrictions
observations
status
created_by
updated_by
created_at
updated_at
deleted_at
```

### driver_documents

```text
id
driver_id
document_type
name
file_path
file_disk
mime_type
size
issued_at
expires_at
status
notes
uploaded_by
created_at
updated_at
deleted_at
```

### driver_medical_leaves

```text
id
driver_id
leave_type
started_at
ended_at
reason
description
file_path
status
registered_by
approved_by
approved_at
created_at
updated_at
deleted_at
```

### driver_conduct_reports

```text
id
driver_id
event_date
type
severity
description
action_taken
file_path
status
created_by
reviewed_by
reviewed_at
created_at
updated_at
deleted_at
```

### driver_termination_records

```text
id
driver_id
termination_date
termination_type
reason
description
rehire_status
rehire_reason
file_path
created_by
approved_by
approved_at
created_at
updated_at
```

### driver_traffic_fine_checks

```text
id
driver_id
license_number
vehicle_plate
checked_at
source
result_status
result_summary
amount
file_path
next_check_at
notes
created_by
created_at
updated_at
```

## Reglas Importantes Para Choferes

- El codigo interno debe ser unico.
- El documento de identidad debe ser unico.
- El numero de licencia debe ser unico cuando aplique.
- Un chofer solo debe tener un estado laboral activo a la vez.
- La licencia vencida debe generar alerta.
- Los documentos con vencimiento deben aparecer en alertas.
- La eliminacion debe ser logica con `softDeletes`.
- Los cambios de estado, cargo o departamento deben crear historial.
- Solo usuarios con permiso pueden ver reportes de conducta, permisos medicos y decisiones de recontratacion.
- Un chofer despedido debe tener registro de razon de salida.
- El campo de recontratacion debe ser obligatorio al cerrar una desvinculacion.

## Roadmap Recomendado

### Version 0.1

- Login.
- Roles y permisos basicos.
- CRUD de choferes.
- Catalogos de departamentos, cargos y tipos de contrato.
- Datos de licencia.

### Version 0.2

- Expediente completo.
- Documentos adjuntos.
- Alertas de documentos y licencias vencidas.
- Auditoria.

### Version 0.3

- Permisos medicos y otros permisos.
- Reportes de conducta.
- Desvinculacion y recontratacion.
- Exportaciones.

### Version 0.4

- Mecanica de autobuses.
- Facturas de reparacion.
- Repuestos usados.

### Version 0.5

- Almacen.
- Gasoil.
- Reportes operativos.

## Comandos Iniciales Sugeridos

Cuando se decida iniciar el codigo:

```bash
composer create-project laravel/laravel backend
npm create vite@latest frontend -- --template vue
```

El proyecto se inicio con backend y frontend separados:

```text
backend/
frontend/
```

### Backend

Configuracion local para MySQL de XAMPP:

```text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=administrativo
DB_USERNAME=root
DB_PASSWORD=
```

Antes de migrar, crear la base de datos `administrativo` en phpMyAdmin o MySQL.

Comandos:

```bash
cd backend
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

API inicial:

```text
GET    /api/health
GET    /api/drivers
POST   /api/drivers
GET    /api/drivers/{driver}
PUT    /api/drivers/{driver}
DELETE /api/drivers/{driver}
```

### Frontend

Comandos:

```bash
cd frontend
npm install
npm run dev
```

El frontend consume por defecto:

```text
VITE_API_URL=http://localhost:8000/api
```

### Pendiente por Fecha/SSL Local

La terminal local reporto errores `CERT_NOT_YET_VALID` al instalar paquetes de Composer/npm, probablemente porque la fecha del sistema esta en 2022. Antes de instalar dependencias nuevas, corregir fecha/hora de Windows.

Si el frontend muestra:

```text
"vite" no se reconoce como un comando interno o externo
```

significa que `npm install` no termino correctamente y por eso no existe `node_modules/.bin/vite`.

Pasos recomendados en Windows:

1. Corregir fecha y hora de Windows. El log de npm mostro `2022-05-25`; debe estar en la fecha actual.
2. Cerrar terminales, editores o procesos que puedan estar usando `frontend/node_modules`.
3. Limpiar la instalacion incompleta:

```powershell
cd C:\Users\ZBOOK\Documents\Proyectos\administrativo\frontend
Remove-Item -Recurse -Force node_modules
Remove-Item -Force package-lock.json
npm cache clean --force
npm install
npm run dev
```

Si PowerShell no deja ejecutar npm por politicas de scripts, usar:

```powershell
npm.cmd install
npm.cmd run dev
```

Paquetes pendientes:

```bash
cd backend
composer require laravel/sanctum spatie/laravel-permission spatie/laravel-activitylog
```

Despues de instalar esos paquetes se activaran autenticacion API, roles/permisos y auditoria.

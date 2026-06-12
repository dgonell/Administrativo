<?php

namespace Database\Seeders;

use App\Models\ContractType;
use App\Models\Department;
use App\Models\Driver;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DriverCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $departments = collect([
            'Operaciones Santo Domingo',
            'Operaciones Santiago',
            'Logistica y Despacho',
            'Mantenimiento de Flota',
            'Recursos Humanos',
        ])->mapWithKeys(fn (string $name) => [$name => Department::query()->firstOrCreate(['name' => $name])]);

        $positions = collect([
            'Chofer de reparto',
            'Chofer de carga pesada',
            'Chofer ejecutivo',
            'Chofer suplente',
            'Supervisor de ruta',
        ])->mapWithKeys(fn (string $name) => [$name => Position::query()->firstOrCreate(['name' => $name])]);

        $contractTypes = collect([
            'Indefinido',
            'Temporal',
            'Por servicios',
        ])->mapWithKeys(fn (string $name) => [$name => ContractType::query()->firstOrCreate(['name' => $name])]);

        $documents = [
            ['name' => 'Cedula de identidad y electoral', 'document_type' => 'cedula', 'requires_expiration_date' => false],
            ['name' => 'Licencia de conducir dominicana', 'document_type' => 'licencia', 'requires_expiration_date' => true],
            ['name' => 'Certificado medico', 'document_type' => 'certificado_medico', 'requires_expiration_date' => true],
            ['name' => 'Certificado de no antecedentes', 'document_type' => 'no_antecedentes', 'requires_expiration_date' => true],
            ['name' => 'Contrato firmado', 'document_type' => 'contrato', 'requires_expiration_date' => false],
            ['name' => 'Referencias laborales', 'document_type' => 'referencias', 'requires_expiration_date' => false],
        ];

        foreach ($documents as $document) {
            DB::table('driver_required_documents')->updateOrInsert(
                ['document_type' => $document['document_type']],
                $document + ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $drivers = [
            ['Juan', 'Martinez Diaz', '001-1857426-3', '809-555-0101', 'juan.martinez@caribetrans.do', 'Av. 27 de Febrero 145, Santo Domingo', 'Operaciones Santo Domingo', 'Chofer de reparto', 'Indefinido', '2018-02-12', 'active', 'yes', 'DOP-102345', 'Categoria 3', 'Apto para rutas urbanas y entregas programadas.'],
            ['Pedro', 'Santos Ramirez', '001-2468135-7', '829-555-0102', 'pedro.santos@caribetrans.do', 'Ensanche La Fe, Santo Domingo', 'Logistica y Despacho', 'Chofer de carga pesada', 'Indefinido', '2019-06-03', 'active', 'yes', 'DOP-102346', 'Categoria 4', 'Experiencia en carga interprovincial.'],
            ['Jose', 'Almonte Reyes', '031-1187654-2', '849-555-0103', 'jose.almonte@caribetrans.do', 'Gurabo, Santiago de los Caballeros', 'Operaciones Santiago', 'Supervisor de ruta', 'Indefinido', '2017-09-18', 'active', 'yes', 'DOP-102347', 'Categoria 4', 'Supervisa rutas del Cibao.'],
            ['Miguel', 'Castillo Mendez', '001-3344556-8', '809-555-0104', 'miguel.castillo@caribetrans.do', 'Los Mina, Santo Domingo Este', 'Operaciones Santo Domingo', 'Chofer de reparto', 'Temporal', '2022-01-10', 'dismissal', 'review', 'DOP-102348', 'Categoria 3', 'Despedido por decision administrativa.'],
            ['Rafael', 'Jimenez Pena', '047-1258746-1', '829-555-0105', 'rafael.jimenez@caribetrans.do', 'Villa Olga, Santiago', 'Operaciones Santiago', 'Chofer de carga pesada', 'Indefinido', '2020-03-24', 'active', 'yes', 'DOP-102349', 'Categoria 4', 'Disponible para viajes nocturnos.'],
            ['Luis', 'Fernandez Soto', '001-5689741-0', '849-555-0106', 'luis.fernandez@caribetrans.do', 'Bella Vista, Santo Domingo', 'Logistica y Despacho', 'Chofer ejecutivo', 'Indefinido', '2016-11-07', 'active', 'yes', 'DOP-102350', 'Categoria 2', 'Asignado a traslados ejecutivos.'],
            ['Manuel', 'Torres Cabrera', '402-2147896-5', '809-555-0107', 'manuel.torres@caribetrans.do', 'San Isidro, Santo Domingo Este', 'Mantenimiento de Flota', 'Chofer suplente', 'Temporal', '2023-04-15', 'active', 'review', 'DOP-102351', 'Categoria 3', 'Cubre rutas durante mantenimientos.'],
            ['Andres', 'Rosario Guzman', '001-7412589-6', '829-555-0108', 'andres.rosario@caribetrans.do', 'Herrera, Santo Domingo Oeste', 'Operaciones Santo Domingo', 'Chofer de carga pesada', 'Indefinido', '2015-08-20', 'resignation', 'yes', 'DOP-102352', 'Categoria 4', 'Renuncia registrada con historial favorable.'],
            ['Victor', 'Molina Peralta', '031-9988776-4', '849-555-0109', 'victor.molina@caribetrans.do', 'La Otra Banda, Santiago', 'Operaciones Santiago', 'Chofer de reparto', 'Indefinido', '2021-02-01', 'active', 'yes', 'DOP-102353', 'Categoria 3', 'Rutas locales de Santiago.'],
            ['Francisco', 'Nunez Batista', '001-6655443-2', '809-555-0110', 'francisco.nunez@caribetrans.do', 'Villa Mella, Santo Domingo Norte', 'Logistica y Despacho', 'Chofer de carga pesada', 'Por servicios', '2024-01-22', 'active', 'review', 'DOP-102354', 'Categoria 4', 'Contrato por servicios en temporada alta.'],
            ['Carlos', 'Mejia Vargas', '402-3366998-0', '829-555-0111', 'carlos.mejia@caribetrans.do', 'Haina, San Cristobal', 'Mantenimiento de Flota', 'Chofer suplente', 'Temporal', '2022-07-11', 'resignation', 'review', 'DOP-102355', 'Categoria 3', 'Renuncia por disponibilidad limitada.'],
            ['Daniel', 'Peguero Ortiz', '001-7788996-1', '849-555-0112', 'daniel.peguero@caribetrans.do', 'Gazcue, Santo Domingo', 'Operaciones Santo Domingo', 'Chofer ejecutivo', 'Indefinido', '2019-12-02', 'active', 'yes', 'DOP-102356', 'Categoria 2', 'Manejo defensivo certificado.'],
            ['Roberto', 'Cruz Tejada', '031-4455667-9', '809-555-0113', 'roberto.cruz@caribetrans.do', 'Tamboril, Santiago', 'Operaciones Santiago', 'Chofer de carga pesada', 'Indefinido', '2018-10-09', 'dismissal', 'no', 'DOP-102357', 'Categoria 4', 'Despedido por incidente vial.'],
            ['Hector', 'Morales Aquino', '001-1122334-5', '829-555-0114', 'hector.morales@caribetrans.do', 'Los Alcarrizos, Santo Domingo', 'Logistica y Despacho', 'Chofer de reparto', 'Indefinido', '2020-05-28', 'active', 'yes', 'DOP-102358', 'Categoria 3', 'Especialista en entregas de ultima milla.'],
            ['Emilio', 'Rivas Santana', '402-9876543-1', '849-555-0115', 'emilio.rivas@caribetrans.do', 'Ensanche Ozama, Santo Domingo Este', 'Operaciones Santo Domingo', 'Chofer de carga pesada', 'Indefinido', '2017-01-16', 'dismissal', 'no', 'DOP-102359', 'Categoria 4', 'Despedido por incumplimiento de protocolo.'],
            ['Ramon', 'Brito Lora', '001-2244668-0', '809-555-0116', 'ramon.brito@caribetrans.do', 'Arroyo Hondo, Santo Domingo', 'Mantenimiento de Flota', 'Supervisor de ruta', 'Indefinido', '2014-06-30', 'active', 'yes', 'DOP-102360', 'Categoria 4', 'Coordina inspecciones de salida.'],
            ['Alejandro', 'Diaz Polanco', '031-1357924-6', '829-555-0117', 'alejandro.diaz@caribetrans.do', 'Pontezuela, Santiago', 'Operaciones Santiago', 'Chofer de reparto', 'Temporal', '2023-09-05', 'active', 'review', 'DOP-102361', 'Categoria 3', 'Ruta Santiago-Moca-La Vega.'],
            ['Nelson', 'Matos Arias', '001-5791358-4', '849-555-0118', 'nelson.matos@caribetrans.do', 'Pantoja, Santo Domingo Oeste', 'Logistica y Despacho', 'Chofer suplente', 'Temporal', '2021-07-19', 'resignation', 'yes', 'DOP-102362', 'Categoria 3', 'Renuncia registrada con disponibilidad de reingreso.'],
            ['Oscar', 'Guerrero Tavarez', '402-8642097-3', '809-555-0119', 'oscar.guerrero@caribetrans.do', 'Boca Chica, Santo Domingo', 'Operaciones Santo Domingo', 'Chofer de carga pesada', 'Indefinido', '2016-03-14', 'active', 'yes', 'DOP-102363', 'Categoria 4', 'Cubre puerto multimodal Caucedo.'],
            ['Julio', 'Herrera Pichardo', '001-6420864-7', '829-555-0120', 'julio.herrera@caribetrans.do', 'Licey al Medio, Santiago', 'Operaciones Santiago', 'Chofer de reparto', 'Por servicios', '2024-03-04', 'active', 'review', 'DOP-102364', 'Categoria 3', 'Refuerzo operativo para el Cibao.'],
            ['Samuel', 'Vasquez Pena', '001-7531594-8', '809-555-0121', 'samuel.vasquez@caribetrans.do', 'Ciudad Juan Bosch, Santo Domingo Este', 'Operaciones Santo Domingo', 'Chofer de reparto', 'Indefinido', '2020-08-17', 'active', 'yes', 'DOP-102365', 'Categoria 3', 'Responsable de entregas residenciales en Santo Domingo Este.'],
            ['Edwin', 'Sanchez Marte', '031-7539512-0', '829-555-0122', 'edwin.sanchez@caribetrans.do', 'Los Jardines Metropolitanos, Santiago', 'Operaciones Santiago', 'Chofer de carga pesada', 'Indefinido', '2018-12-03', 'active', 'yes', 'DOP-102366', 'Categoria 4', 'Asignado a rutas Santiago-Puerto Plata.'],
            ['Jorge', 'Acosta Mieses', '402-1472583-6', '849-555-0123', 'jorge.acosta@caribetrans.do', 'Manoguayabo, Santo Domingo Oeste', 'Logistica y Despacho', 'Chofer suplente', 'Temporal', '2023-11-13', 'active', 'review', 'DOP-102367', 'Categoria 3', 'Refuerzo para rutas metropolitanas y entregas especiales.'],
            ['Felix', 'Cordero Medina', '001-3692581-9', '809-555-0124', 'felix.cordero@caribetrans.do', 'La Vega, Republica Dominicana', 'Operaciones Santiago', 'Supervisor de ruta', 'Indefinido', '2017-05-08', 'active', 'yes', 'DOP-102368', 'Categoria 4', 'Supervisa rutas del corredor Cibao Central.'],
        ];

        foreach ($drivers as $index => $data) {
            [$firstName, $lastName, $identityDocument, $phone, $email, $address, $department, $position, $contractType, $hireDate, $status, $rehireStatus, $licenseNumber, $category, $notes] = $data;
            $code = 'SEED-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $issuedAt = now()->subYears(2)->subDays($index * 11)->toDateString();
            $expiresAt = now()->addMonths(8 + ($index % 18))->toDateString();

            $driver = Driver::query()->updateOrCreate(
                ['identity_document' => $identityDocument],
                [
                    'code' => $code,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'identity_document' => $identityDocument,
                    'tss_worker_code' => 'TSS-'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                    'birth_date' => now()->subYears(31 + ($index % 18))->subDays($index * 19)->toDateString(),
                    'phone' => $phone,
                    'contact_name' => $firstName.' '.$lastName,
                    'email' => $email,
                    'address' => $address,
                    'department_id' => $departments[$department]->id,
                    'position_id' => $positions[$position]->id,
                    'contract_type_id' => $contractTypes[$contractType]->id,
                    'hire_date' => $hireDate,
                    'termination_date' => $status !== 'active' ? now()->subMonths(2 + ($index % 8))->toDateString() : null,
                    'status' => $status,
                    'rehire_status' => $rehireStatus,
                    'rehire_notes' => $rehireStatus === 'no' ? 'No recomendado para reingreso por decision de gerencia.' : 'Puede ser considerado segun necesidad operativa.',
                    'notes' => $notes,
                ]
            );

            $finalCode = 'CH'.$driver->id;
            if ($driver->code !== $finalCode) {
                $driver->update(['code' => $finalCode]);
            }
            $code = $finalCode;

            $driver->license()->updateOrCreate(
                ['license_number' => $licenseNumber],
                [
                    'category' => $category,
                    'issued_at' => $issuedAt,
                    'expires_at' => $expiresAt,
                    'issuing_entity' => 'Direccion General de Seguridad de Transito y Transporte Terrestre',
                    'restrictions' => $index % 5 === 0 ? 'Usa lentes correctivos.' : 'Sin restricciones registradas.',
                    'observations' => 'Licencia verificada contra expediente fisico.',
                    'status' => 'active',
                ]
            );

            foreach ($documents as $document) {
                $driver->documents()->updateOrCreate(
                    ['document_type' => $document['document_type'], 'name' => $document['name']],
                    [
                        'file_path' => "expedientes/{$code}/{$document['document_type']}.pdf",
                        'file_disk' => 'local',
                        'mime_type' => 'application/pdf',
                        'size' => 240000 + ($index * 3500),
                        'issued_at' => now()->subMonths(10 + ($index % 6))->toDateString(),
                        'expires_at' => $document['requires_expiration_date'] ? now()->addMonths(10 + ($index % 12))->toDateString() : null,
                        'status' => 'valid',
                        'notes' => 'Documento recibido y validado por Recursos Humanos.',
                    ]
                );
            }

            $driver->medicalLeaves()->updateOrCreate(
                ['started_at' => now()->subMonths(5)->subDays($index)->toDateString(), 'leave_type' => 'Permiso medico'],
                [
                    'ended_at' => now()->subMonths(5)->subDays($index - 2)->toDateString(),
                    'reason' => 'Consulta medica programada',
                    'description' => 'Permiso respaldado por certificado medico local.',
                    'file_path' => "expedientes/{$code}/permiso_medico.pdf",
                    'status' => 'approved',
                    'approved_at' => now()->subMonths(5)->subDays($index - 1),
                ]
            );

            $driver->conductReports()->updateOrCreate(
                ['event_date' => now()->subMonths(3)->subDays($index)->toDateString(), 'type' => 'Evaluacion de conducta'],
                [
                    'severity' => $status === 'dismissal' ? 'high' : 'low',
                    'description' => $status === 'dismissal' ? 'Incumplimiento reiterado de protocolo de entrega.' : 'Evaluacion rutinaria sin hallazgos criticos.',
                    'action_taken' => $status === 'dismissal' ? 'Caso cerrado por gerencia de operaciones.' : 'Seguimiento normal de supervisor de ruta.',
                    'file_path' => "expedientes/{$code}/conducta.pdf",
                    'status' => 'closed',
                    'reviewed_at' => now()->subMonths(3)->subDays($index - 1),
                ]
            );

            $driver->trafficFineChecks()->updateOrCreate(
                ['license_number' => $licenseNumber, 'vehicle_plate' => 'L'.str_pad((string) (230000 + $index), 6, '0', STR_PAD_LEFT)],
                [
                    'checked_at' => now()->subDays(12 + $index),
                    'source' => 'Revision interna',
                    'result_status' => $index % 6 === 0 ? 'with_fines' : 'clear',
                    'result_summary' => $index % 6 === 0 ? 'Tiene una infraccion menor pendiente de conciliacion.' : 'Sin infracciones pendientes registradas.',
                    'amount' => $index % 6 === 0 ? 1500 : 0,
                    'file_path' => "expedientes/{$code}/consulta_multas.pdf",
                    'next_check_at' => now()->addMonths(1)->toDateString(),
                    'notes' => 'Consulta registrada para control operativo.',
                ]
            );

            if ($status !== 'active') {
                $driver->terminationRecords()->updateOrCreate(
                    ['termination_date' => $driver->termination_date?->toDateString()],
                    [
                        'termination_type' => $status,
                        'reason' => $status === 'dismissal' ? 'Incumplimiento de protocolo' : 'Renuncia voluntaria',
                        'description' => 'Registro de salida documentado por Recursos Humanos.',
                        'rehire_status' => $rehireStatus,
                        'rehire_reason' => $rehireStatus === 'no' ? 'No recomendado por decision administrativa.' : 'Puede evaluarse si existe vacante compatible.',
                        'file_path' => "expedientes/{$code}/salida.pdf",
                        'approved_at' => now()->subMonth(),
                    ]
                );
            }
        }
    }
}

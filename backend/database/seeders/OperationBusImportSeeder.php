<?php

namespace Database\Seeders;

use App\Models\OperationBus;
use App\Models\OperationBusHistory;
use Illuminate\Database\Seeder;

class OperationBusImportSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->buses() as $data) {
            $bus = OperationBus::query()->updateOrCreate(
                ['fleet_number' => $data['fleet_number']],
                $data
            );

            OperationBusHistory::query()->firstOrCreate(
                [
                    'operation_bus_id' => $bus->id,
                    'action' => 'Importado',
                ],
                [
                    'fleet_number' => $bus->fleet_number,
                    'detail' => 'Registro migrado desde el sistema anterior.',
                ]
            );
        }
    }

    private function buses(): array
    {
        return [
            ['legacy_id' => 1, 'fleet_number' => '104', 'brand' => 'Scania', 'model' => 'K410 B6X2', 'plate' => 'I093652', 'chassis' => '9BSK6X200H3897150', 'year' => 2017, 'status' => 'operational'],
            ['legacy_id' => 2, 'fleet_number' => '111', 'brand' => 'Scania', 'model' => 'PARADISO K410', 'plate' => 'I072892', 'chassis' => '9BSK4X200G3883468', 'year' => 2016, 'status' => 'operational'],
            ['legacy_id' => 3, 'fleet_number' => '121', 'brand' => 'Hyundai', 'model' => 'UNIVERSE NOBLE', 'plate' => 'I127122', 'chassis' => 'KMJKG18RPDC007177', 'year' => 2013, 'status' => 'operational'],
            ['legacy_id' => 4, 'fleet_number' => '122', 'brand' => 'KIA', 'model' => 'GRANDBIRD', 'plate' => 'I126912', 'chassis' => 'KNGGBK1W2CK005833', 'year' => 2012, 'status' => 'operational'],
            ['legacy_id' => 5, 'fleet_number' => '123', 'brand' => 'Scania', 'model' => 'K410 B6X2', 'plate' => 'I093853', 'chassis' => '9BSK4X22H3896888', 'year' => 2017, 'status' => 'operational'],
            ['legacy_id' => 6, 'fleet_number' => '125', 'brand' => 'Scania', 'model' => 'K360 B4X2', 'plate' => 'I085401', 'chassis' => '9BSK4X200J3916913', 'year' => 2018, 'status' => 'operational'],
            ['legacy_id' => 7, 'fleet_number' => '129', 'brand' => 'KIA', 'model' => 'GRANDBIRD', 'plate' => 'I105929', 'chassis' => 'KNGGBK6W2EK009330', 'year' => 2014, 'status' => 'operational'],
            ['legacy_id' => 8, 'fleet_number' => '134', 'brand' => 'KIA', 'model' => 'GRANDBIRD', 'plate' => 'I110009', 'chassis' => 'KNGGBK6W2EK008577', 'year' => 2014, 'status' => 'operational'],
            ['legacy_id' => 9, 'fleet_number' => '137', 'brand' => 'Scania', 'model' => 'K410', 'plate' => 'I068185', 'chassis' => '9BSK4X200G3883456', 'year' => 2016, 'status' => 'workshop'],
            ['legacy_id' => 10, 'fleet_number' => '138', 'brand' => 'KIA', 'model' => 'GRANDBIRD PAEKWAY', 'plate' => 'I127466', 'chassis' => 'KNGGBK1W2EK008702', 'year' => 2014, 'status' => 'operational'],
            ['legacy_id' => 11, 'fleet_number' => '139', 'brand' => 'Scania', 'model' => 'K410', 'plate' => 'I092158', 'chassis' => '9BSK6X200J3916915', 'year' => 2018, 'status' => 'operational'],
            ['legacy_id' => 12, 'fleet_number' => '140', 'brand' => 'KIA', 'model' => 'GRANDBIRD', 'plate' => 'I104856', 'chassis' => 'KNGGBK6W2DK008320', 'year' => 2013, 'status' => 'workshop', 'notes' => 'Se le esta arreglando el aire.'],
            ['legacy_id' => 13, 'fleet_number' => '146', 'brand' => 'KIA', 'model' => 'GRANDBIRD', 'plate' => 'I108336', 'chassis' => 'KNGGBK1W2DK007283', 'year' => 2013, 'status' => 'operational'],
            ['legacy_id' => 14, 'fleet_number' => '150', 'brand' => 'Scania', 'model' => 'K410', 'plate' => 'I092159', 'chassis' => '9BSK6X200J3916890', 'year' => 2018, 'status' => 'operational'],
            ['legacy_id' => 15, 'fleet_number' => '151', 'brand' => 'Scania', 'model' => 'K360 B4X2', 'plate' => 'I082466', 'chassis' => '9BSK4X200J3916885', 'year' => 2018, 'status' => 'operational'],
            ['legacy_id' => 16, 'fleet_number' => '152', 'brand' => 'KIA', 'model' => 'GRANDBIRD', 'plate' => 'I109441', 'chassis' => 'KNGGBK1W2CK005688', 'year' => 2012, 'status' => 'workshop'],
            ['legacy_id' => 17, 'fleet_number' => '154', 'brand' => 'KIA', 'model' => 'GRANDBIRD', 'plate' => 'I108337', 'chassis' => 'KNKGGBK1W2DK007150', 'year' => 2013, 'status' => 'operational'],
            ['legacy_id' => 18, 'fleet_number' => '155', 'brand' => 'Hyundai', 'model' => 'UNIVERSE NOBLE', 'plate' => 'I089218', 'chassis' => 'KMJKL18NPAC003323', 'year' => 2010, 'status' => 'operational'],
            ['legacy_id' => 19, 'fleet_number' => '156', 'brand' => 'Hyundai', 'model' => 'UNIVERSE', 'plate' => 'I074954', 'chassis' => 'KMJKL18NP8C001685', 'year' => 2008, 'status' => 'operational'],
            ['legacy_id' => 21, 'fleet_number' => 'CAMIONETA', 'brand' => 'Toyota', 'model' => 'KUN15L-PRMDY', 'vehicle_type' => 'van', 'plate' => 'L224050', 'chassis' => 'MROES12G503301833', 'year' => 2008, 'status' => 'operational'],
        ];
    }
}

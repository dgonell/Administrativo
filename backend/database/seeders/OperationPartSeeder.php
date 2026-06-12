<?php

namespace Database\Seeders;

use App\Models\OperationPart;
use Illuminate\Database\Seeder;

class OperationPartSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Aceite de motor', 'category' => 'oil'],
            ['name' => 'Filtro de aceite', 'category' => 'filter'],
            ['name' => 'Filtro de aire', 'category' => 'filter'],
            ['name' => 'Filtro de combustible', 'category' => 'filter'],
            ['name' => 'Filtro hidraulico', 'category' => 'filter'],
            ['name' => 'Neumatico', 'category' => 'tire'],
            ['name' => 'Pastillas de freno', 'category' => 'part'],
            ['name' => 'Valvula de frenos', 'category' => 'part'],
            ['name' => 'Radiador', 'category' => 'part'],
            ['name' => 'Manguera de intercooler', 'category' => 'part'],
            ['name' => 'Correa de motor', 'category' => 'part'],
            ['name' => 'Bateria', 'category' => 'part'],
        ] as $part) {
            OperationPart::query()->updateOrCreate(['name' => $part['name']], $part);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\FinanceClient;
use App\Models\FinanceQuote;
use App\Models\FinanceRoute;
use Illuminate\Database\Seeder;

class FinanceQuoteSeeder extends Seeder
{
    public function run(): void
    {
        $clients = collect([
            ['name' => 'Constructora Caribe', 'rnc' => '130-45892-1', 'contact' => 'Laura Medina', 'phone' => '809-555-0140', 'email' => 'operaciones@constructoracaribe.com'],
            ['name' => 'Colegio Duarte', 'rnc' => '101-88475-2', 'contact' => 'Ramon Paredes', 'phone' => '809-555-0198', 'email' => 'administracion@colegioduarte.edu.do'],
            ['name' => 'Hotel Costa Azul', 'rnc' => '132-44881-5', 'contact' => 'Mariel Rivas', 'phone' => '809-555-0181', 'email' => 'eventos@hotelcostaazul.com'],
            ['name' => 'Grupo Industrial Cibao', 'rnc' => '124-90817-4', 'contact' => 'Victor Almanzar', 'phone' => '809-555-0177', 'email' => 'logistica@industrialcibao.com'],
            ['name' => 'Universidad Metropolitana', 'rnc' => '101-77844-8', 'contact' => 'Claudia Santos', 'phone' => '809-555-0162', 'email' => 'compras@umet.edu.do'],
        ])->map(fn (array $data) => FinanceClient::query()->updateOrCreate(
            ['rnc' => $data['rnc']],
            $data
        ))->values();

        $routes = collect([
            ['name' => 'Santo Domingo - Punta Cana', 'distance' => 195, 'base_rate' => 28000],
            ['name' => 'Santo Domingo - Santiago', 'distance' => 155, 'base_rate' => 22000],
            ['name' => 'Santo Domingo - Puerto Plata', 'distance' => 215, 'base_rate' => 31000],
            ['name' => 'Santo Domingo - La Romana', 'distance' => 125, 'base_rate' => 19000],
            ['name' => 'Santiago - Samana', 'distance' => 230, 'base_rate' => 33000],
            ['name' => 'Santo Domingo - Bani', 'distance' => 65, 'base_rate' => 12000],
        ])->map(fn (array $data) => FinanceRoute::query()->updateOrCreate(
            ['name' => $data['name']],
            $data
        ))->values();

        for ($index = 1; $index <= 20; $index++) {
            $client = $clients[($index - 1) % $clients->count()];
            $lineCount = $index % 3 === 0 ? 2 : 1;
            $lines = [];

            for ($line = 0; $line < $lineCount; $line++) {
                $route = $routes[($index + $line - 1) % $routes->count()];
                $buses = 1 + (($index + $line) % 3);
                $days = 1 + (($index + $line) % 2);
                $pricePerBus = (float) $route->base_rate + (($index % 4) * 1500);

                $lines[] = [
                    'route_name' => $route->name,
                    'capacity' => [22, 32, 45, 55][($index + $line) % 4].' pasajeros',
                    'days' => $days,
                    'buses' => $buses,
                    'price_per_bus' => $pricePerBus,
                    'final_price' => $buses * $pricePerBus,
                    'pickup_point' => 'Punto coordinado '.$index,
                    'dropoff_point' => 'Destino operativo '.$index,
                    'schedule' => $index % 2 === 0 ? '7:00 AM - 6:00 PM' : '8:00 AM - 5:00 PM',
                ];
            }

            $finalPrice = array_sum(array_column($lines, 'final_price'));
            $quote = FinanceQuote::query()->updateOrCreate(
                ['number' => 'COT-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT)],
                [
                    'finance_client_id' => $client->id,
                    'service_date' => now()->addDays($index)->toDateString(),
                    'valid_until' => now()->addDays(max($index - 3, 1))->toDateString(),
                    'payment_terms' => '50% para reservar, 50% antes del servicio',
                    'notes' => 'Cotizacion de ejemplo para servicio corporativo.',
                    'final_price' => $finalPrice,
                ]
            );

            $quote->lines()->delete();
            $quote->lines()->createMany($lines);
        }
    }
}

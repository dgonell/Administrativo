<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_manages_finance_clients_routes_and_quotes(): void
    {
        $this->authenticate();

        $client = $this->postJson('/api/finance-clients', [
            'name' => 'Grupo Empresarial Norte',
            'rnc' => '131-55555-1',
            'contact' => 'Ana Perez',
            'phone' => '809-555-1111',
            'email' => 'ana@example.com',
        ])
            ->assertCreated()
            ->assertJsonPath('name', 'Grupo Empresarial Norte')
            ->json();

        $this->putJson("/api/finance-clients/{$client['id']}", [
            'name' => 'Grupo Empresarial Norte SRL',
            'rnc' => '131-55555-1',
            'contact' => 'Ana Perez',
            'phone' => '809-555-1111',
            'email' => 'ana@example.com',
        ])->assertOk()->assertJsonPath('name', 'Grupo Empresarial Norte SRL');

        $route = $this->postJson('/api/finance-routes', [
            'name' => 'Santo Domingo - Bani',
            'distance' => 65,
            'base_rate' => 12000,
        ])
            ->assertCreated()
            ->assertJsonPath('name', 'Santo Domingo - Bani')
            ->json();

        $this->putJson("/api/finance-routes/{$route['id']}", [
            'name' => 'Santo Domingo - Bani',
            'distance' => 67,
            'base_rate' => 12500,
        ])->assertOk()->assertJsonPath('distance', 67);

        $quote = $this->postJson('/api/finance-quotes', [
            'finance_client_id' => $client['id'],
            'status' => 'draft',
            'service_date' => '2026-06-15',
            'valid_until' => '2026-06-10',
            'payment_terms' => 'Pago contra factura',
            'final_price' => 50000,
            'lines' => [
                [
                    'route_name' => 'Santo Domingo - Bani',
                    'capacity' => '45 pasajeros',
                    'days' => 1,
                    'buses' => 2,
                    'price_per_bus' => 25000,
                    'final_price' => 50000,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('number', 'COT-0001')
            ->assertJsonCount(1, 'lines')
            ->json();

        $this->putJson("/api/finance-quotes/{$quote['id']}", [
            'finance_client_id' => $client['id'],
            'status' => 'sent',
            'service_date' => '2026-06-16',
            'valid_until' => '2026-06-11',
            'payment_terms' => 'Pago contra factura',
            'final_price' => 75000,
            'lines' => [
                [
                    'route_name' => 'Santo Domingo - Bani',
                    'capacity' => '55 pasajeros',
                    'days' => 1,
                    'buses' => 3,
                    'price_per_bus' => 25000,
                    'final_price' => 75000,
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('final_price', '75000.00')
            ->assertJsonPath('lines.0.buses', 3);

        $this->getJson('/api/finance-clients/history')->assertOk()->assertJsonCount(2);
        $this->getJson('/api/finance-routes/history')->assertOk()->assertJsonCount(2);
        $this->getJson('/api/finance-quotes')->assertOk()->assertJsonCount(1);
    }
}

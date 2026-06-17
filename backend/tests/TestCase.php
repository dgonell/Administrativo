<?php

namespace Tests;

use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function authenticate(): void
    {
        $this->seed(AccessControlSeeder::class);

        $token = $this->postJson('/api/auth/login', [
            'email' => env('ADMIN_EMAIL', 'admin@administrativo.local'),
            'password' => env('ADMIN_PASSWORD', '1234'),
        ])->json('token');

        $this->withHeader('Authorization', "Bearer {$token}");
    }
}

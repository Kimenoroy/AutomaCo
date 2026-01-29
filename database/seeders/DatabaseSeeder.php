<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\EmailProviderSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\ActivationCode;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**Seed the application's database.*/
    public function run(): void
    {// Seedear proveedores de email$this->call([EmailProviderSeeder::class,]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin Facturacion',
            'email' => 'admin@empresa.com',
            'password' => bcrypt('password123'),
        ]);


        ActivationCode::create([
            'user_id' => null, // <--- IMPORTANTE: Sin dueño al nacer
            'code_hash' => hash('sha256', '123456'), // Código conocido para probar
            'is_used' => false,
        ]);

    }
}
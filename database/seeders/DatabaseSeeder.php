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
  public function run(): void{// Seedear proveedores de email$this->call([EmailProviderSeeder::class,]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin Facturacion',
        'email' => 'admin@empresa.com',
        'password' => bcrypt('password123'),
        ]);

        // 3. Usuario de Prueba (Inactivo)
        $userInactivo = User::factory()->create([
            'name' => 'Usuario Nuevo',
            'email' => 'nuevo@prueba.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        ActivationCode::create([
            'user_id' => $userInactivo->id,
            'code_hash' => hash('sha256', 'CODIGO2026'),
            'is_used' => false,
        ]);

    }
}
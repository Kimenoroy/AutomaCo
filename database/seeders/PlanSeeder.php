<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::create([
            'name' => 'Plan AutomaCo Básico',
            'price' => 25.00,
            'description' => 'Acceso completo al sistema AutomaCo con 1 código de activación.',
            'is_active' => true
        ]);
    }
}

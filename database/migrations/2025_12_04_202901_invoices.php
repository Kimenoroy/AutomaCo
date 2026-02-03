<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("invoices", function (Blueprint $table) {
            $table->id();

            // Identificador único de la factura (Necesario para buscar el registro)
            $table->string('generation_code')->unique();

            // Ubicación de los archivos en tu storage
            $table->string('pdf_path')->nullable();  // Ruta del PDF
            $table->string('json_path')->nullable(); // Ruta del JSON (archivo)

            // Contenido del JSON guardado en la base de datos (para acceso rápido sin leer el archivo)
            $table->json('raw_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
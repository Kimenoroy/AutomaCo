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

            /*Identificadores unicos del DTE */
            $table->string('generation_code')->unique(); //Codigo de generacion (Evitar duplicados)
            $table->string('control_number')->nullable(); // Numero de control
            $table->text('stamp')->nullable(); //Sello de resepcion

            /*Datos del emisor/provedor*/
            $table-> string('provider_name'); //Proveedor
            $table->string('proveider_nit')->nullable(); //NIT / RFC

            /*Detellaes Financieros*/
            $table->dateTime('issue_date'); //Fecha de emision
            $table->decimal('taxable_amount', 12, 2)->default; //Monto grabado
            $table->decimal('favial_amount', 10, 2)->default; //Fovial
            $table->decimal('total_amount',12,2)->default; //Monto total

            /*Estado y archivos*/
            $table->string('status')->default('procesado'); //status (procesado, anulado)
            $table->string('pdf_path')->nullable(); // Ruta al archivo PDF guardado
            $table->string('json_path')->nullable(); // Ruta al archivo JSON guardado

            /*Campo extra para guardar todo el JSON por seguridad*/
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

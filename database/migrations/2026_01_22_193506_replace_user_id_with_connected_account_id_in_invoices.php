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
        // Opcional: Limpiar tabla para evitar errores de datos huerfanos
        // DB::table('invoices')->truncate(); 
    
        Schema::table('invoices', function (Blueprint $table) {
            // Eliminamos la relación vieja
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
    
            // Creamos la nueva relación OBLIGATORIA
            $table->foreignId('connected_account_id')
                  ->after('id')
                  ->constrained('connected_accounts')
                  ->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->dropForeign(['connected_account_id']);
        $table->dropColumn('connected_account_id');
        // Restaurar la vieja (puede fallar si hay datos, pero es para rollback)
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    });
}
};

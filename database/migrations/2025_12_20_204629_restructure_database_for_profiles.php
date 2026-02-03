<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Limpiamos la tabla 'users' (Quitamos la relación 1 a 1 antigua)
        Schema::table('users', function (Blueprint $table) {
            // Verificamos si existe la llave foránea antes de borrarla para evitar errores
            if (Schema::hasColumn('users', 'email_provider_id')) {
                // El nombre de la foránea suele ser users_email_provider_id_foreign
                $table->dropForeign(['email_provider_id']);
                $table->dropColumn('email_provider_id');
            }
        });

        // Creamos la tabla de "Perfiles"
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->id();

            // El Dueño de la cuenta 
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // El proveedor (Google / Outlook)
            $table->foreignId('email_provider_id')->constrained('email_providers');

            // --- DATOS VISUALES Y DE IDENTIDAD ---
            $table->string('provider_user_id'); // ID único 
            $table->string('email');            // Correo 
            $table->string('name');             // Nombre 
            $table->string('avatar', 2048)->nullable(); // URL de la foto de perfil

            // --- TOKENS ---
            $table->text('token');          // Access Token
            $table->text('refresh_token')->nullable(); // Refresh Token
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Evitamos duplicados: No puedes vincular la misma cuenta dos veces
            $table->unique(['user_id', 'email_provider_id', 'provider_user_id'], 'unique_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connected_accounts');
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('email_provider_id')->nullable()->constrained('email_providers');
        });
    }
};

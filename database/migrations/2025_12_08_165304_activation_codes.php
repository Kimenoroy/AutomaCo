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
        Schema::create('activation_codes', function (Blueprint $table) {
         $table->id();
         $table->string('code_hash')->unique();
         $table->boolean('is_used')->default(false);
         $table->timestamp('used_at')->nullable();

         //relacion con la table users
         $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
         $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activation_codes');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};

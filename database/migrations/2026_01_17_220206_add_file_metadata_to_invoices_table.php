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
        Schema::table('invoices', function (Blueprint $table) {
            
            $table->string('client_name')->nullable()->after('user_id');
            $table->string('pdf_original_name')->nullable()->after('pdf_path');
            $table->string('json_original_name')->nullable()->after('json_path');
            $table->dateTime('pdf_created_at')->nullable()->after('pdf_original_name');
            $table->dateTime('json_created_at')->nullable()->after('json_original_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'client_name', 
                'pdf_original_name', 
                'json_original_name', 
                'pdf_created_at', 
                'json_created_at'
            ]);
        });
    }
};
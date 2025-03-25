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
        Schema::table('contract_data', function (Blueprint $table) {
            // Ajouter les nouvelles colonnes
            $table->string('full_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_data', function (Blueprint $table) {
            // Supprimer les nouvelles colonnes
            $table->dropColumn(['full_name', 'birth_date', 'address', 'phone']);
        });
    }
};

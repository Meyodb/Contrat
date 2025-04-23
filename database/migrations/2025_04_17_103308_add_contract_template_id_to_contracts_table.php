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
        Schema::table('contracts', function (Blueprint $table) {
            // Vérifier si la colonne existe déjà
            if (!Schema::hasColumn('contracts', 'contract_template_id')) {
                $table->foreignId('contract_template_id')->nullable()
                    ->after('admin_id')
                    ->constrained('contract_templates')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'contract_template_id')) {
                $table->dropForeign(['contract_template_id']);
                $table->dropColumn('contract_template_id');
            }
        });
    }
};

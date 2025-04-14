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
            $table->foreignId('parent_contract_id')
                  ->nullable()
                  ->after('contract_template_id')
                  ->constrained('contracts')
                  ->onDelete('set null');
            
            $table->string('avenant_number')->nullable()->after('title');
            $table->string('contract_type')->default('contract')->after('avenant_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['parent_contract_id']);
            $table->dropColumn('parent_contract_id');
            $table->dropColumn('avenant_number');
            $table->dropColumn('contract_type');
        });
    }
}; 
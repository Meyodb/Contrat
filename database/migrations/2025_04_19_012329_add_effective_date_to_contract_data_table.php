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
            $table->date('effective_date')->nullable()->after('trial_period_months');
            $table->date('original_contract_date')->nullable()->after('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_data', function (Blueprint $table) {
            $table->dropColumn('effective_date');
            $table->dropColumn('original_contract_date');
        });
    }
};

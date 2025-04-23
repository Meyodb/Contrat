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
        Schema::create('avenants', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->date('initial_contract_date');
            $table->date('effective_date');
            $table->date('signing_date');
            $table->float('current_hours');
            $table->float('current_salary');
            $table->float('current_hourly_rate');
            $table->float('new_hours');
            $table->float('new_salary');
            $table->float('new_hourly_rate');
            $table->text('motif');
            $table->enum('status', ['draft', 'pending', 'signed', 'rejected'])->default('draft');
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avenants');
    }
}; 
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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_template_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->enum('status', [
                'draft', 
                'submitted', 
                'in_review', 
                'admin_signed', 
                'employee_signed', 
                'completed', 
                'rejected'
            ])->default('draft');
            $table->text('admin_notes')->nullable();
            $table->string('admin_signature')->nullable();
            $table->string('employee_signature')->nullable();
            $table->string('final_document_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('admin_signed_at')->nullable();
            $table->timestamp('employee_signed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};

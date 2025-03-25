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
            // Champs pour l'employé
            $table->string('signature_id')->nullable()->after('employee_signature');
            $table->string('document_hash')->nullable()->after('signature_id');
            
            // Champs pour l'administrateur
            $table->string('admin_signature_id')->nullable()->after('admin_signature');
            $table->string('admin_document_hash')->nullable()->after('admin_signature_id');
            
            // Champ pour stocker le moment où le certificat a été généré
            $table->timestamp('certificate_generated_at')->nullable()->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'signature_id',
                'document_hash',
                'admin_signature_id',
                'admin_document_hash',
                'certificate_generated_at'
            ]);
        });
    }
};

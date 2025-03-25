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
            // Supprimer les anciennes colonnes si elles existent
            if (Schema::hasColumn('contract_data', 'field_name')) {
                $table->dropColumn('field_name');
            }
            if (Schema::hasColumn('contract_data', 'field_value')) {
                $table->dropColumn('field_value');
            }
            if (Schema::hasColumn('contract_data', 'field_type')) {
                $table->dropColumn('field_type');
            }
            if (Schema::hasColumn('contract_data', 'section')) {
                $table->dropColumn('section');
            }
            if (Schema::hasColumn('contract_data', 'admin_only')) {
                $table->dropColumn('admin_only');
            }
            if (Schema::hasColumn('contract_data', 'is_admin_field')) {
                $table->dropColumn('is_admin_field');
            }

            // Rendre les colonnes existantes nullables si elles existent déjà
            if (Schema::hasColumn('contract_data', 'full_name')) {
                $table->string('full_name')->nullable()->change();
            } else {
                $table->string('full_name')->nullable();
            }
            
            if (Schema::hasColumn('contract_data', 'birth_date')) {
                $table->date('birth_date')->nullable()->change();
            } else {
                $table->date('birth_date')->nullable();
            }
            
            if (Schema::hasColumn('contract_data', 'address')) {
                $table->text('address')->nullable()->change();
            } else {
                $table->text('address')->nullable();
            }
            
            if (Schema::hasColumn('contract_data', 'phone')) {
                $table->string('phone')->nullable()->change();
            } else {
                $table->string('phone')->nullable();
            }
            
            // Ajouter les nouvelles colonnes pour les informations personnelles
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('gender', ['M', 'F'])->nullable();
            $table->string('birth_place')->nullable();
            $table->string('nationality')->nullable();
            $table->string('social_security_number')->nullable();
            $table->string('email')->nullable();
            $table->text('bank_details')->nullable();
            $table->string('photo_path')->nullable();
            
            // Ajouter les colonnes pour les informations du contrat (remplies par l'admin)
            $table->decimal('work_hours', 5, 2)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_signing_date')->nullable();
            $table->integer('trial_period_months')->nullable();
            $table->decimal('overtime_hours_20', 5, 2)->nullable();
            
            // Ajouter les colonnes pour les champs calculés
            $table->decimal('monthly_hours', 8, 2)->nullable();
            $table->decimal('weekly_hours', 8, 2)->nullable();
            $table->decimal('monthly_gross_salary', 10, 2)->nullable();
            $table->date('trial_period_end_date')->nullable();
            $table->decimal('monthly_overtime', 10, 2)->nullable();
            $table->decimal('weekly_overtime', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_data', function (Blueprint $table) {
            // Supprimer toutes les nouvelles colonnes ajoutées
            $table->dropColumn([
                'first_name', 'last_name', 'gender', 'birth_place', 'nationality',
                'social_security_number', 'email', 'bank_details', 'photo_path',
                'work_hours', 'hourly_rate', 'contract_start_date', 'contract_signing_date',
                'trial_period_months', 'overtime_hours_20',
                'monthly_hours', 'weekly_hours', 'monthly_gross_salary',
                'trial_period_end_date', 'monthly_overtime', 'weekly_overtime'
            ]);
            
            // Recréer les colonnes originales
            $table->string('field_name');
            $table->text('field_value')->nullable();
            $table->string('field_type')->nullable();
            $table->string('section')->nullable();
            $table->boolean('admin_only')->default(false);
            $table->boolean('is_admin_field')->default(false);
        });
    }
};

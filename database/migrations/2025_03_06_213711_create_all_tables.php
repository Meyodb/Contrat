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
        // Création de la table users (si elle n'existe pas déjà)
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('profile_photo_path')->nullable();
                $table->boolean('is_admin')->default(false);
                $table->string('signature_image')->nullable();
                $table->boolean('archived')->default(false);
                $table->timestamp('archived_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Création de la table contracts
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->timestamp('employee_signed_at')->nullable();
            $table->timestamp('admin_signed_at')->nullable();
            $table->string('signature_verification_token')->nullable();
            $table->string('email_verification_status')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->date('effective_date')->nullable();
            $table->unsignedBigInteger('parent_contract_id')->nullable();
            $table->timestamps();
            
            $table->foreign('parent_contract_id')
                ->references('id')
                ->on('contracts')
                ->nullOnDelete();
        });

        // Création de la table contract_data
        Schema::create('contract_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('nationality')->nullable();
            $table->string('social_security_number')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('contract_type')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('contract_signing_date')->nullable();
            $table->double('monthly_gross_salary', 8, 2)->nullable();
            $table->integer('weekly_hours')->nullable();
            $table->integer('monthly_hours')->nullable();
            $table->integer('trial_period_months')->nullable();
            $table->date('trial_period_end_date')->nullable();
            $table->integer('weekly_overtime')->nullable();
            $table->integer('monthly_overtime')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        // Création de la table company_infos
        Schema::create('company_infos', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('legal_form')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('siret')->nullable();
            $table->string('rcs')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('share_capital')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_data');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('company_infos');
        // Note: On ne supprime pas la table users car elle peut être créée par d'autres migrations
    }
};

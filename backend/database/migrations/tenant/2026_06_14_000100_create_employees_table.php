<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha de empleado. `dni` e `iban` se cifran a nivel de aplicación (cast encrypted).
 * `clock_code` es el PIN de 8 dígitos para el reloj kiosk (único en el tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('work_center_id')->nullable()->constrained('work_centers')->nullOnDelete();
            $table->foreignUuid('agreement_id')->nullable()->constrained('agreements')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Datos básicos
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('second_last_name', 100)->nullable();
            $table->enum('treatment', ['sr', 'sra', 'dr', 'dra'])->nullable();
            $table->text('dni')->nullable();              // cifrado
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->string('nationality', 50)->nullable();

            // Fichaje
            $table->char('clock_code', 8)->nullable()->unique();
            $table->boolean('exempt_from_clock')->default(false);

            // Empresa
            $table->string('department', 100)->nullable();
            $table->string('job_position', 100)->nullable();
            $table->string('job_category', 100)->nullable();
            $table->enum('employment_status', ['active', 'inactive', 'leave'])->default('active');
            $table->date('hire_date')->nullable();

            // Contacto profesional
            $table->string('email_company')->nullable();
            $table->string('phone_company', 20)->nullable();
            $table->string('mobile_company', 20)->nullable();

            // Contacto personal
            $table->string('email_personal')->nullable();
            $table->string('phone_personal', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();

            // Bancario
            $table->text('iban')->nullable();             // cifrado

            // Vehículo / meta
            $table->string('vehicle_plate', 15)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'active']);
            $table->index('clock_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

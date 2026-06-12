<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relación del empleado con la empresa: contratos, salarios, beneficios sociales,
 * adelantos y gastos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 100);
            $table->date('date_start');
            $table->date('date_end')->nullable();
            $table->decimal('working_hours', 4, 2)->nullable();
            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('employee_id');
        });

        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->date('effective_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('employee_id');
        });

        Schema::create('employee_social_benefits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 100);
            $table->decimal('amount', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('employee_id');
        });

        Schema::create('employee_advances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->enum('status', ['pendiente', 'descontado'])->default('pendiente');
            $table->timestamps();
            $table->index('employee_id');
        });

        Schema::create('employee_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->enum('status', ['pendiente', 'aprobado', 'rechazado', 'pagado'])->default('pendiente');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_expenses');
        Schema::dropIfExists('employee_advances');
        Schema::dropIfExists('employee_social_benefits');
        Schema::dropIfExists('employee_salaries');
        Schema::dropIfExists('employee_contracts');
    }
};

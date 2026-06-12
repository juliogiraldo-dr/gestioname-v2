<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perfil del empleado: formación, comportamiento, materiales cedidos, propuestas de
 * mejora, documentos y contactos familiares.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_qualifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['titulacion', 'curso', 'certificado', 'conocimiento', 'experiencia']);
            $table->string('name');
            $table->string('institution')->nullable();
            $table->date('date_obtained')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('employee_id');
        });

        Schema::create('employee_behavior_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['felicitacion', 'amonestacion', 'sancion']);
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('document_path')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('employee_id');
        });

        Schema::create('employee_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('serial_number', 100)->nullable();
            $table->date('delivery_date')->nullable();
            $table->date('return_date')->nullable();
            $table->enum('status', ['entregado', 'devuelto', 'perdido'])->default('entregado');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('employee_id');
        });

        Schema::create('employee_improvement_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['enviada', 'en_revision', 'aceptada', 'rechazada'])->default('enviada');
            $table->text('response')->nullable();
            $table->timestamps();
            $table->index('employee_id');
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 100)->nullable();
            $table->string('file_path');
            $table->boolean('visible_to_employee')->default(true);
            $table->timestamps();
            $table->index('employee_id');
        });

        Schema::create('employee_family_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('relationship', 50)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_family_contacts');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employee_improvement_proposals');
        Schema::dropIfExists('employee_materials');
        Schema::dropIfExists('employee_behavior_records');
        Schema::dropIfExists('employee_qualifications');
    }
};

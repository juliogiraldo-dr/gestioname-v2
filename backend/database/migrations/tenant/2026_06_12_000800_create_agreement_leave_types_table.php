<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipos de ausencia/presencia configurables por convenio (vacaciones, médico, horas
 * extra...). `count_in` indica si se contabilizan en días u horas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agreement_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('type', ['ausencia', 'presencia']);
            $table->enum('count_in', ['dias', 'horas']);
            $table->boolean('subtracts_vacation')->default(false);
            $table->boolean('requires_document')->default(false);
            $table->boolean('visible_to_employee')->default(true);
            $table->smallInteger('max_days')->nullable();
            $table->decimal('max_hours', 5, 2)->nullable();
            $table->timestamps();

            $table->index('agreement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_leave_types');
    }
};

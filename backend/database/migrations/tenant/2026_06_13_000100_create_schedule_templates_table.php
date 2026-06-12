<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plantillas de horario reutilizables por empresa y año.
 *
 * - fijo:     tramos horarios concretos (tabla schedule_time_ranges) + tolerancia.
 * - flexible: rango de entrada (flex_start_min..max) + horas a cumplir al día.
 * - libre:    bolsa de horas (diaria/semanal/mensual/anual).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 7)->default('#5EB8D0');
            $table->enum('type', ['fijo', 'flexible', 'libre']);
            $table->smallInteger('year');
            $table->smallInteger('tolerance_minutes')->default(0);
            // flexible
            $table->time('flex_start_min')->nullable();
            $table->time('flex_start_max')->nullable();
            $table->decimal('flex_hours_day', 4, 2)->nullable();
            // libre
            $table->decimal('free_hours_daily', 4, 2)->nullable();
            $table->decimal('free_hours_weekly', 5, 2)->nullable();
            $table->decimal('free_hours_monthly', 6, 2)->nullable();
            $table->decimal('free_hours_annual', 7, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_templates');
    }
};

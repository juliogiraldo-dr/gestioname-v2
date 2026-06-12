<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asignación de una plantilla de horario a cada día de un calendario.
 * Un día solo puede tener una plantilla (unique calendar_id + date).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('calendar_id')->constrained('work_calendars')->cascadeOnDelete();
            $table->date('date');
            $table->foreignUuid('schedule_template_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['calendar_id', 'date']);
            $table->index(['calendar_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_days');
    }
};

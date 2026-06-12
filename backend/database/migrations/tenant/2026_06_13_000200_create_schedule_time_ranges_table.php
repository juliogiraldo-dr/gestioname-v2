<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tramos horarios de una plantilla de tipo `fijo` (p. ej. 09:00-14:00 y 15:00-18:00).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_time_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_template_id')->constrained()->cascadeOnDelete();
            $table->time('time_start');
            $table->time('time_end');
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('schedule_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_time_ranges');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Festivos. Globales del tenant; se asocian a centros concretos vía pivote
 * (sin centros asociados = aplica a todos).
 *
 * - `repeatable = true`  → mismo día cada año, en `day_of_year` (1-366).
 * - `repeatable = false` → fecha exacta en `date` (festivos movibles, p. ej. Viernes Santo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['nacional', 'autonomico', 'local']);
            $table->boolean('repeatable')->default(false);
            $table->smallInteger('day_of_year')->nullable();
            $table->date('date')->nullable();
            $table->string('province', 100)->nullable();
            $table->string('locality', 100)->nullable();
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad obligatoria de correcciones de fichaje (ET 34.9). Cada fila registra el
 * valor anterior, el nuevo, quién y por qué. Es inmutable: nunca se modifica ni se borra.
 * `new_clocked_at` nulo = el fichaje fue eliminado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('old_clocked_at')->nullable();
            $table->timestamp('new_clocked_at')->nullable();
            $table->text('reason');
            $table->timestamp('created_at')->nullable();

            $table->index('attendance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};

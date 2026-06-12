<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de fichajes (ET art. 34.9). El registro original es inmutable: las
 * correcciones se anotan en `attendance_corrections` y el borrado es lógico (soft delete),
 * de modo que el histórico se conserva los 4 años exigidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('milestone_id')->constrained('attendance_milestones')->restrictOnDelete();
            $table->timestamp('clocked_at');
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->enum('method', ['web', 'movil', 'kiosk', 'manual'])->default('kiosk');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'clocked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

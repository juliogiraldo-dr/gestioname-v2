<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitudes de ausencia/presencia. Flujo: pendiente → aprobada/rechazada (o cancelada
 * por el propio empleado mientras esté pendiente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('leave_type_id')->constrained('agreement_leave_types')->restrictOnDelete();
            $table->date('date_start');
            $table->date('date_end');
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->decimal('total_days', 4, 1)->nullable();
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('document_path')->nullable();
            $table->enum('status', ['pendiente', 'aprobada', 'rechazada', 'cancelada'])->default('pendiente');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'date_start']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};

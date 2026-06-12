<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de comunicaciones (email masivo a socios o empleados). Guarda el alcance,
 * los filtros aplicados, el número de destinatarios y quién la envió.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('audience');                     // socios | empleados
            $table->foreignUuid('entity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->json('filters')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->string('trigger')->default('manual');   // manual | recordatorio_cuota
            $table->foreignUuid('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['audience', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};

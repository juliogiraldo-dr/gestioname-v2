<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuración del recordatorio automático de cuota por entidad: cuántos días antes
 * del cierre del ejercicio se avisa a los socios con la cuota pendiente, y la plantilla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quota_reminder_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->unsignedSmallInteger('days_before')->default(15);
            $table->string('subject')->default('Recordatorio de cuota');
            $table->text('body')->default('Te recordamos que tu cuota de socio está pendiente de pago.');
            $table->date('last_run_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quota_reminder_settings');
    }
};

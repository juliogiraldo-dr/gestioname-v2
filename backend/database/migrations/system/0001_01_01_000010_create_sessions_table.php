<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `sessions` — infraestructura del framework, vive en `public`.
 * La API es stateless (tokens Sanctum), pero se mantiene la tabla por compatibilidad
 * con el driver de sesión `database` si llegara a usarse. `user_id` es solo un índice,
 * sin clave foránea (los usuarios viven en schemas de tenant, no en `public`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};

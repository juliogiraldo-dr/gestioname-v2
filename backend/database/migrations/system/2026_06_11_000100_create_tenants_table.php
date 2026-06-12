<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `tenants` — vive SIEMPRE en el schema `public`.
 * Es la tabla maestra del sistema: cada fila corresponde a una empresa/entidad
 * y a su propio schema PostgreSQL (`{subdomain}`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Subdominio = nombre del schema PostgreSQL del tenant.
            // Validado como etiqueta DNS en el middleware antes de usarse en search_path.
            $table->string('subdomain')->unique();
            $table->string('plan')->default('free');   // free | essential | professional | business | enterprise
            $table->string('status')->default('trial'); // trial | active | suspended
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

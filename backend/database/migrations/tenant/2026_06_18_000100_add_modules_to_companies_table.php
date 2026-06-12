<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activación de módulos por empresa: RRHH/fichajes y Socios/asociaciones.
 * RRHH activo por defecto (núcleo del producto); Socios desactivado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('module_hr')->default(true);
            $table->boolean('module_associations')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['module_hr', 'module_associations']);
        });
    }
};

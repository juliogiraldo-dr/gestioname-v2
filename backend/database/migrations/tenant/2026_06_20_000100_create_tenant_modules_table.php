<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulos activables a nivel de tenant (RRHH, Socios, Tesorería, Nóminas, Comunicaciones).
 *
 * Un tenant puede ser una empresa, una entidad/asociación o ambas; los módulos se activan
 * de forma independiente. Vive en el schema del tenant (configuración propia del tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 50)->unique();   // rrhh | socios | tesoreria | nominas | comunicaciones
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
    }
};

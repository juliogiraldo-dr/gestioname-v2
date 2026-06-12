<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las entidades/asociaciones pasan a pertenecer a una empresa del tenant.
 *
 * Columna añadida como `nullable` para no romper filas existentes; la integridad
 * referencial se garantiza en la capa de aplicación (validación `exists:companies,id`
 * + guard de borrado de empresa). No se añade FK a nivel de BD aquí porque ALTER TABLE
 * con clave foránea no es portable a SQLite (tests).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entities', function (Blueprint $table) {
            $table->uuid('company_id')->nullable()->after('id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('entities', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};

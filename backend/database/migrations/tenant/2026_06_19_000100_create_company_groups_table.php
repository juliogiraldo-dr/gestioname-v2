<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grupos de empresas: un tenant puede agrupar varias empresas bajo un mismo grupo
 * (p. ej. "Grupo Datarecover" con "Datarecover S.L." y "Datarecover Cloud S.L.").
 *
 * `companies.company_group_id` se añade como columna `nullable` sin FK a nivel de BD
 * (ALTER con clave foránea no es portable a SQLite/tests); la integridad se valida en
 * la capa de aplicación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->uuid('company_group_id')->nullable()->after('id');
            $table->index('company_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['company_group_id']);
            $table->dropColumn('company_group_id');
        });

        Schema::dropIfExists('company_groups');
    }
};

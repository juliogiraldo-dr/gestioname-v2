<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrección conceptual: las entidades/asociaciones son independientes a nivel de tenant,
 * NO cuelgan de una empresa. Se elimina `entities.company_id` (los datos de las entidades
 * y sus socios/pagos/gastos se conservan; solo se rompe el vínculo con la empresa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entities', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('entities', function (Blueprint $table) {
            $table->uuid('company_id')->nullable()->after('id');
            $table->index('company_id');
        });
    }
};

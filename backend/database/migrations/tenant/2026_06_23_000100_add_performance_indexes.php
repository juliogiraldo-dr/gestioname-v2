<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice compuesto que faltaba para acelerar los pagos por socio/ejercicio.
 *
 * Ya existen desde sus migraciones de creación: employees(company_id, active),
 * attendances(employee_id, clocked_at) y members(entity_id, status).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_payments', function (Blueprint $table) {
            $table->index(['member_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('member_payments', function (Blueprint $table) {
            $table->dropIndex(['member_id', 'year']);
        });
    }
};

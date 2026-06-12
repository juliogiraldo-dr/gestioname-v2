<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entidad/asociación (puede haber varias por tenant). `opening_balance` es el saldo
 * inicial del ejercicio para el cálculo de tesorería.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['pena', 'ampa', 'asociacion_cultural', 'vecinal', 'club', 'cofradia', 'otro']);
            $table->string('cif', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->smallInteger('fiscal_year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};

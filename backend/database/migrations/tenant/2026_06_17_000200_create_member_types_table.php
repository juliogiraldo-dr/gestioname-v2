<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipos de socio con su cuota y periodicidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->enum('fee_periodicity', ['anual', 'semestral', 'trimestral', 'mensual'])->default('anual');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('entity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_types');
    }
};

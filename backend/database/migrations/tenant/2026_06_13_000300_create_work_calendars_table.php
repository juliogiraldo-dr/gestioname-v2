<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calendario laboral anual de una empresa. Cada día se asocia a una plantilla de horario
 * (tabla calendar_days).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_calendars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 7)->default('#0F2756');
            $table->smallInteger('year');
            $table->string('country', 3)->default('ESP');
            $table->string('province', 100)->nullable();
            $table->string('locality', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_calendars');
    }
};

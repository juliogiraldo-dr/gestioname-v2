<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Convenios laborales de una empresa: horas anuales y régimen de vacaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('annual_hours', 6, 2);
            $table->smallInteger('vacation_days');
            $table->enum('vacation_type', ['laborables', 'naturales']);
            $table->date('vacation_expiry')->nullable();   // fecha límite de disfrute
            $table->date('exercise_close')->nullable();     // cierre del ejercicio
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};

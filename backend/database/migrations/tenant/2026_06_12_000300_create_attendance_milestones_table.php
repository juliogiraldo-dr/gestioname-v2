<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hitos de fichaje personalizables por empresa (ENTRADA, SALIDA, salida a comer...).
 * Cada hito es de tipo `entrada` o `salida` y puede mostrarse u ocultarse en informes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_milestones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#90cbe8');   // hex
            $table->enum('type', ['entrada', 'salida']);
            $table->boolean('show_in_report')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_milestones');
    }
};

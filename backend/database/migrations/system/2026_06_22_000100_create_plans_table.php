<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planes de suscripción (schema public). `limits` y `modules_allowed` son JSON.
 * Un límite `null` significa ilimitado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 8, 2)->default(0);
            $table->decimal('price_yearly', 8, 2)->nullable();
            $table->boolean('is_public')->default(true);
            $table->json('limits');             // { companies, employees, entities, members, users }
            $table->json('modules_allowed');    // ["rrhh","socios",...]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personalización de límites/módulos de un tenant concreto sin cambiar su plan (public).
 * Cualquier campo null → se hereda del plan base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('limits')->nullable();
            $table->json('modules_allowed')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_overrides');
    }
};

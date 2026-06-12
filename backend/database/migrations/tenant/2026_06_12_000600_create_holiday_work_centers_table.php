<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivote festivo ↔ centro de trabajo. Si un festivo no tiene filas aquí, aplica a todos
 * los centros del tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_work_centers', function (Blueprint $table) {
            $table->foreignUuid('holiday_id')->constrained('holidays')->cascadeOnDelete();
            $table->foreignUuid('work_center_id')->constrained('work_centers')->cascadeOnDelete();

            $table->primary(['holiday_id', 'work_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_work_centers');
    }
};

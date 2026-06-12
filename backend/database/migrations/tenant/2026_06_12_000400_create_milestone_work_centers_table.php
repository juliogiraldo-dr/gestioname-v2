<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivote hito ↔ centro de trabajo: en qué centros está disponible cada hito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_work_centers', function (Blueprint $table) {
            $table->foreignUuid('milestone_id')->constrained('attendance_milestones')->cascadeOnDelete();
            $table->foreignUuid('work_center_id')->constrained('work_centers')->cascadeOnDelete();

            $table->primary(['milestone_id', 'work_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_work_centers');
    }
};

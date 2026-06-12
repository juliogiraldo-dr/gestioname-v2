<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivote calendario ↔ empleado (qué calendario laboral sigue cada empleado).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_employees', function (Blueprint $table) {
            $table->foreignUuid('calendar_id')->constrained('work_calendars')->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->primary(['calendar_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_employees');
    }
};

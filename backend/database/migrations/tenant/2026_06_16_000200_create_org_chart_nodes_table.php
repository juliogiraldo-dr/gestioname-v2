<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nodos del organigrama por centro de trabajo. `parent_id` define la jerarquía;
 * `receives_notifications` marca quién recibe avisos (p. ej. de solicitudes de ausencia).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_chart_nodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('work_center_id')->constrained('work_centers')->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->boolean('receives_notifications')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('work_center_id');
        });

        // FK autorreferenciada en un paso aparte: la tabla (con su PK) ya existe, de modo
        // que PostgreSQL reconoce la clave única referenciada.
        Schema::table('org_chart_nodes', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('org_chart_nodes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_chart_nodes');
    }
};

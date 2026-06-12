<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Convenios aplicables a cada centro de trabajo. Permite filtrar, al asignar un empleado
 * a un centro, los convenios disponibles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_center_agreements', function (Blueprint $table) {
            $table->foreignUuid('work_center_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('agreement_id')->constrained()->cascadeOnDelete();
            $table->primary(['work_center_id', 'agreement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_center_agreements');
    }
};

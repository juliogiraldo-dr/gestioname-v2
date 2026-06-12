<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recibos de nómina por empleado. El PDF se almacena en disco (disk local,
 * storage/app/private). La gestoría o el admin los suben; el empleado los descarga
 * desde su portal. No contienen datos sensibles indexables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');   // 1-12
            $table->smallInteger('year');
            $table->string('file_path');
            $table->string('original_name');
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // Una nómina por empleado/mes/año (re-subir reemplaza).
            $table->unique(['employee_id', 'year', 'month']);
            $table->index(['employee_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};

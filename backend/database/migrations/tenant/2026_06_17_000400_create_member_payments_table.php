<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pagos de cuota de los socios por ejercicio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('year');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pagado', 'parcial', 'pendiente'])->default('pendiente');
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['efectivo', 'transferencia', 'bizum', 'domiciliacion', 'otro'])->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entity_id', 'year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_payments');
    }
};

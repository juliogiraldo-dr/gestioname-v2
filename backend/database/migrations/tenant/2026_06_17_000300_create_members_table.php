<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Socios de una entidad. `dni` se cifra a nivel de aplicación (cast encrypted),
 * por eso es `text` (LOPD/GDPR).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entity_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_type_id')->nullable()->constrained('member_types')->nullOnDelete();
            $table->string('member_number', 20)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 200)->nullable();
            $table->text('dni')->nullable();               // cifrado
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->date('date_join')->nullable();
            $table->date('date_leave')->nullable();
            $table->enum('status', ['activo', 'baja_voluntaria', 'baja_impagada', 'honor', 'pendiente'])->default('activo');
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['entity_id', 'status']);
            $table->index(['entity_id', 'member_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

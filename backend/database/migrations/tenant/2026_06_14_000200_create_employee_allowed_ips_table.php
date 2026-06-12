<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IPs permitidas para fichar de un empleado (control de IP).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_allowed_ips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->string('description', 100)->nullable();
            $table->timestamps();

            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_allowed_ips');
    }
};

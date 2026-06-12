<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de auditoría de las acciones del super-admin (schema public).
 * Los ids de usuario son UUID de schemas de tenant, por eso no llevan FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('superadmin_audit_log', function (Blueprint $table) {
            $table->id();
            $table->uuid('superadmin_user_id')->nullable();
            $table->string('action', 100);
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('target_user_id')->nullable();
            $table->json('details')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('tenant_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('superadmin_audit_log');
    }
};

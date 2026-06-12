<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `users` — vive en el schema de CADA tenant (nunca en `public`).
 * Se aplica vía `tenant:create` / `migrate:tenants`, no con el `migrate` normal.
 *
 * Campos sensibles (DNI) cifrados en BD con el cast `encrypted` del modelo (LOPD/GDPR).
 * Por eso `dni` es TEXT y sin índice único: el ciphertext no es determinista.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            // Nullable: los usuarios que entran solo por magic link no tienen contraseña.
            $table->string('password')->nullable();
            $table->text('dni')->nullable();                 // cifrado (cast encrypted)
            $table->string('phone')->nullable();
            // Código de fichaje de 8 dígitos para el reloj kiosk. Único dentro del tenant.
            $table->string('code_fichaje', 8)->nullable()->unique();
            $table->string('avatar')->nullable();            // ruta del fichero
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};

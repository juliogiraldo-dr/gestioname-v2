<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaces públicos de descarga de un solo uso (válidos 72 h). Permiten compartir
 * un fichero (nómina, informe, suenlace.dat) con una gestoría externa sin darle
 * acceso a la plataforma. La tabla es a la vez el registro de descargas:
 * `created_by`/`created_at` = quién generó y cuándo; `used_at`/`downloaded_ip` = la descarga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token_hash', 64)->unique();   // sha256 hex; el token en claro solo se muestra al generarlo
            $table->string('disk')->default('local');
            $table->string('file_path');
            $table->string('filename');                    // nombre con el que se descarga
            $table->string('kind')->default('documento');  // nomina | informe | suenlace | documento
            $table->string('label')->nullable();           // descripción legible para el registro
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();       // un solo uso
            $table->string('downloaded_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['expires_at', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_tokens');
    }
};

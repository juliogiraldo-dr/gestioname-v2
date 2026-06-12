<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca blanca ampliada: tipografía y logo subido como fichero.
 * `logo_path` sigue sirviendo como URL pública (externa o del logo subido);
 * `logo_file` guarda la ruta interna del fichero subido (si lo hay).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_branding', function (Blueprint $table) {
            $table->string('font')->nullable()->after('primary_color');
            $table->string('logo_file')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_branding', function (Blueprint $table) {
            $table->dropColumn(['font', 'logo_file']);
        });
    }
};

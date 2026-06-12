<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los módulos pasan a gestionarse a nivel de tenant (`tenant_modules`), no por empresa.
 * Se eliminan las banderas de módulo de `companies`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['module_hr', 'module_associations']);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('module_hr')->default(true);
            $table->boolean('module_associations')->default(false);
        });
    }
};

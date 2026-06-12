<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Si el centro exige geolocalización al fichar de forma presencial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_centers', function (Blueprint $table) {
            $table->boolean('location_required')->default(false)->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('work_centers', function (Blueprint $table) {
            $table->dropColumn('location_required');
        });
    }
};

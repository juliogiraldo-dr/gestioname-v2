<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

/**
 * Festivos nacionales de España precargados para 2025 y 2026, en el schema del tenant
 * activo. Idempotente: no duplica si ya existen (clave nombre + fecha).
 *
 * Se cargan como fecha exacta (repeatable=false) en cada año, incluido el Viernes Santo
 * (movible). Los festivos autonómicos/locales los añade cada cliente desde la app.
 */
class NationalHolidaySeeder extends Seeder
{
    /** @var list<int> */
    private const YEARS = [2025, 2026];

    /**
     * Festivos nacionales de fecha fija (mm-dd).
     *
     * @var list<array{0: string, 1: string}>
     */
    private const FIXED = [
        ['Año Nuevo', '01-01'],
        ['Epifanía del Señor', '01-06'],
        ['Fiesta del Trabajo', '05-01'],
        ['Asunción de la Virgen', '08-15'],
        ['Fiesta Nacional de España', '10-12'],
        ['Todos los Santos', '11-01'],
        ['Día de la Constitución', '12-06'],
        ['Inmaculada Concepción', '12-08'],
        ['Natividad del Señor', '12-25'],
    ];

    /** Viernes Santo (movible) por año. */
    private const GOOD_FRIDAY = [
        2025 => '2025-04-18',
        2026 => '2026-04-03',
    ];

    public function run(): void
    {
        foreach (self::YEARS as $year) {
            foreach (self::FIXED as [$name, $monthDay]) {
                $this->ensure($name, sprintf('%d-%s', $year, $monthDay));
            }

            if (isset(self::GOOD_FRIDAY[$year])) {
                $this->ensure('Viernes Santo', self::GOOD_FRIDAY[$year]);
            }
        }
    }

    private function ensure(string $name, string $date): void
    {
        Holiday::firstOrCreate(
            ['name' => $name, 'date' => $date],
            ['type' => 'nacional', 'repeatable' => false],
        );
    }
}

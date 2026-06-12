<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BradfordIndexCalculator;
use PHPUnit\Framework\TestCase;

class BradfordIndexTest extends TestCase
{
    private BradfordIndexCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new BradfordIndexCalculator;
    }

    public function test_formula_penaliza_ausencias_frecuentes(): void
    {
        // 4 episodios de 1 día = 4 días en 4 spells: B = 4² × 4 = 64.
        $this->assertSame(64, $this->calculator->fromSpells([1, 1, 1, 1]));
    }

    public function test_una_sola_ausencia_larga_puntua_poco(): void
    {
        // 1 episodio de 10 días: B = 1² × 10 = 10.
        $this->assertSame(10, $this->calculator->fromSpells([10]));
    }

    public function test_sin_ausencias_es_cero(): void
    {
        $this->assertSame(0, $this->calculator->fromSpells([]));
    }

    public function test_mezcla(): void
    {
        // 3 episodios, 2+3+5 = 10 días: B = 3² × 10 = 90.
        $this->assertSame(90, $this->calculator->fromSpells([2, 3, 5]));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Tests\TenantTestCase;

class SocioToolsTest extends TenantTestCase
{
    private Entity $entity;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entity = Entity::create(['name' => 'Peña', 'type' => 'pena', 'opening_balance' => 0, 'fiscal_year' => 2026]);
        $type = $this->entity->memberTypes()->create(['name' => 'Adulto', 'fee_amount' => 50]);
        $this->member = $this->entity->members()->create(['first_name' => 'Ana', 'last_name' => 'López', 'member_number' => '1', 'member_type_id' => $type->id]);
    }

    public function test_recibo_carnet_y_ficha_son_pdf(): void
    {
        $payment = $this->member->payments()->create(['entity_id' => $this->entity->id, 'year' => 2026, 'amount' => 50, 'status' => 'pagado']);

        foreach ([
            "/member-payments/{$payment->id}/receipt",
            "/members/{$this->member->id}/card",
            "/members/{$this->member->id}/sheet",
            "/entities/{$this->entity->id}/members-pdf",
        ] as $url) {
            $res = $this->getJson($this->url($url))->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $res->streamedContent());
        }
    }

    public function test_exporta_socios_a_excel(): void
    {
        $res = $this->getJson($this->url("/entities/{$this->entity->id}/members/export"))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $res->streamedContent());
    }

    public function test_plantilla_de_socios(): void
    {
        $res = $this->getJson($this->url('/entities/members/template'))->assertOk();
        $this->assertStringStartsWith('PK', $res->streamedContent());
    }

    public function test_backup_exporta_e_importa_json(): void
    {
        $this->member->payments()->create(['entity_id' => $this->entity->id, 'year' => 2026, 'amount' => 50, 'status' => 'pagado']);

        $res = $this->getJson($this->url("/entities/{$this->entity->id}/backup"))->assertOk();
        $json = $res->streamedContent();
        $this->assertStringContainsString('"entity"', $json);

        $file = UploadedFile::fake()->createWithContent('backup.json', $json);
        $this->post($this->url('/entities/backup/import'), ['file' => $file])
            ->assertCreated();

        // Tras importar, hay 2 entidades con socios.
        $this->assertSame(2, Entity::query()->count());
    }
}

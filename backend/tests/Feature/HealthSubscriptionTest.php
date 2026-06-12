<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\PlanSeeder;
use Tests\TenantTestCase;

class HealthSubscriptionTest extends TenantTestCase
{
    public function test_health_devuelve_estado_y_checks(): void
    {
        $res = $this->getJson('/health');

        $res->assertJsonStructure(['status', 'checks' => ['database' => ['ok'], 'redis', 'queue'], 'time']);
        $res->assertJsonPath('checks.database.ok', true);
    }

    public function test_subscription_devuelve_plan_y_uso(): void
    {
        (new PlanSeeder)->run();

        $this->getJson($this->url('/subscription'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['plan', 'trial_ends_at', 'usage' => ['companies' => ['used', 'limit'], 'employees', 'members'], 'plans']]);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_spa_shell_is_served(): void
    {
        $this->get('/')->assertOk()->assertSee('MiniCRM');
    }

    public function test_anonymous_api_access_is_rejected(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }
}

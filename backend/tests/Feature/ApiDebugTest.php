<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDebugTest extends TestCase
{
    /** @test */
    public function api_test_route_returns_200()
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])->getJson('/api/test');
        $response->assertStatus(200)->assertJson(['message' => 'API test route works']);
    }
}

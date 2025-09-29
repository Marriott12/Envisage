<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebDebugTest extends TestCase
{
    /** @test */
    public function web_test_route_returns_200()
    {
        $response = $this->get('/web-test');
        $response->assertStatus(200)->assertJson(['message' => 'Web test route works']);
    }
}

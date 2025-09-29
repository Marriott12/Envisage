<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductsApiTest extends TestCase
{
    /** @test */
    public function products_endpoint_returns_200()
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])->getJson('/api/products');
        $response->assertStatus(200);
    }
}

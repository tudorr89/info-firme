<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_root_renders_the_search_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('CUI');
        $response->assertSee('companie');
    }
}

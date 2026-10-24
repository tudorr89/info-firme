<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
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

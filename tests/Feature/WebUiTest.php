<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebUiTest extends TestCase
{
    public function test_the_terms_page_lists_the_operator_company_data(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('RTI Global Services SRL')
            ->assertSee('51859625')
            ->assertSee('J2025037467000')
            ->assertSee('office@hostingonthe.cloud');
    }

    public function test_the_privacy_policy_lists_the_operator_company_data(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('RTI Global Services SRL')
            ->assertSee('51859625')
            ->assertSee('J2025037467000')
            ->assertSee('office@hostingonthe.cloud');
    }

    public function test_a_non_numeric_cui_returns_404(): void
    {
        $this->get('/companie/not-a-number')->assertNotFound();
    }

    public function test_unknown_routes_return_404(): void
    {
        $this->get('/pagina-care-nu-exista')->assertNotFound();
    }
}

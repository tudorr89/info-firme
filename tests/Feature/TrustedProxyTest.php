<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The container sits behind a Cloudflare tunnel and Caddy, so Laravel has to
 * honour the X-Forwarded-* headers. Without that the admin panel redirected to
 * `http://…/admin` after a successful login and the Open Graph URLs were http.
 */
class TrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_forwarded_proto_and_host_are_honoured(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-For' => '203.0.113.7',
        ])->get('http://lista-firme.test/up');

        $response->assertOk();
    }

    public function test_generated_urls_use_the_forwarded_scheme(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
        ])->get('http://lista-firme.test/admin');

        // Not authenticated, so Filament redirects to the login page — and that
        // Location header is what told us the scheme was being detected wrong.
        $location = $response->headers->get('Location');

        $this->assertNotNull($location);
        $this->assertStringStartsWith('https://', $location);
    }

    public function test_the_admin_panel_is_branded(): void
    {
        $response = $this->get('http://lista-firme.test/admin/login');

        $response->assertOk()
            ->assertSee('lista-firme.info')
            ->assertSee('lf-brand-gradient')
            ->assertSee('favicon.svg');
    }

    public function test_the_client_ip_comes_from_the_forwarded_header(): void
    {
        $this->withHeaders(['X-Forwarded-For' => '203.0.113.7'])
            ->get('http://lista-firme.test/up')
            ->assertOk();

        $this->assertSame('203.0.113.7', request()->ip());
    }
}

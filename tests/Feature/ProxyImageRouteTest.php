<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProxyImageRouteTest extends TestCase
{
    public function test_proxy_image_route_accepts_url_query_parameter(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('image-bytes', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $response = $this->get('/proxy-image?url=' . urlencode('https://example.com/image.png'));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertSee('image-bytes', false);

        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=86400', (string) $response->headers->get('Cache-Control'));
    }
}

<?php

namespace Aanggakrishna\BrowserCacheManager\Tests;

use Aanggakrishna\BrowserCacheManager\Http\Controllers\StatusController;
use Orchestra\Testbench\TestCase;

class StatusControllerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\Aanggakrishna\BrowserCacheManager\BrowserCacheManagerServiceProvider::class];
    }

    public function test_status_endpoint_uses_configured_build_and_safe_recovery_defaults(): void
    {
        config(['browser-cache-manager.version' => '2.4.1', 'browser-cache-manager.enabled' => true]);
        $response = $this->getJson('/browser-cache/status');
        $response->assertOk()->assertJsonPath('version', '2.4.1')->assertJsonPath('enabled', true)->assertJsonPath('recovery.enabled', false);
    }

    public function test_master_switch_disables_status_and_blade_integration(): void
    {
        config(['browser-cache-manager.enabled' => false]);
        $this->getJson('/browser-cache/status')->assertJsonPath('enabled', false);
    }

    public function test_status_supports_conditional_requests(): void
    {
        $first = $this->get('/browser-cache/status');
        $first->assertOk();
        $etag = $first->headers->get('ETag');

        $this->withHeaders(['If-None-Match' => $etag])->get('/browser-cache/status')->assertStatus(304);
    }
}

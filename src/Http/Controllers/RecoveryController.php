<?php

namespace Aanggakrishna\BrowserCacheManager\Http\Controllers;

use Aanggakrishna\BrowserCacheManager\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RecoveryController extends Controller
{
    public function __invoke(Request $request, Settings $settings)
    {
        $values = $settings->all();
        $canClearHttpCache = (bool) config('browser-cache-manager.enabled', true)
            && (bool) $values['enabled']
            && (bool) $values['recovery_enabled']
            && (bool) $values['clear_http_cache'];

        $response = response()->json(['http_cache_clear_requested' => $canClearHttpCache])
            ->header('Cache-Control', 'no-store, private');

        if ($canClearHttpCache) {
            $response->header('Clear-Site-Data', '"cache"');
        }

        return $response;
    }
}

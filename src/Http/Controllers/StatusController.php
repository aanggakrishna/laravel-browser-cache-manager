<?php

namespace Aanggakrishna\BrowserCacheManager\Http\Controllers;

use Aanggakrishna\BrowserCacheManager\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class StatusController extends Controller
{
    public function __invoke(Request $request, Settings $settings)
    {
        $current = (string) config('browser-cache-manager.version', '1.0.0');
        $values = $settings->all();
        $enabled = (bool) ($values['enabled'] && $values['auto_check']);
        $body = [
            'enabled' => $enabled,
            'version' => $current,
            'poll_interval' => max(30, min(3600, (int) config('browser-cache-manager.poll_interval', 300))),
            'recovery' => $enabled && (bool) $values['recovery_enabled'] ? [
                'enabled' => true,
                'force_reload' => (bool) $values['force_reload'],
                'clear_cache_storage' => (bool) $values['clear_cache_storage'],
                'cache_storage_names' => (array) config('browser-cache-manager.recovery.cache_storage_names', []),
                'unregister_service_workers' => (bool) $values['unregister_service_workers'],
                'service_worker_scopes' => (array) config('browser-cache-manager.recovery.service_worker_scopes', []),
                'delete_indexeddb' => (bool) $values['delete_indexeddb'],
                'indexeddb_names' => (array) config('browser-cache-manager.recovery.indexeddb_names', []),
                'clear_local_storage' => (bool) $values['clear_local_storage'],
                'local_storage_prefixes' => (array) config('browser-cache-manager.recovery.local_storage_prefixes', []),
                'reload_once' => (bool) config('browser-cache-manager.recovery.reload_once', true),
            ] : ['enabled' => false],
        ];

        $etag = '"'.hash('sha256', json_encode($body, JSON_THROW_ON_ERROR)).'"';
        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304)->header('ETag', $etag)->header('Cache-Control', 'private, no-cache, max-age=0');
        }

        return response()->json($body)->header('ETag', $etag)->header('Cache-Control', 'private, no-cache, max-age=0');
    }
}

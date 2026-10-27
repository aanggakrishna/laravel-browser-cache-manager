<?php

return [
    // This deployment-level switch can disable the package without a DB lookup.
    'enabled' => (bool) env('BROWSER_CACHE_MANAGER_ENABLED', true),
    // Keep this tied to the same build artifact/release identifier across all instances.
    'version' => env('APP_BUILD_VERSION', env('APP_VERSION', '1.0.0')),
    'poll_interval' => (int) env('BROWSER_CACHE_POLL_INTERVAL', 300),
    'settings_cache_seconds' => (int) env('BROWSER_CACHE_SETTINGS_CACHE_SECONDS', 15),
    'route_prefix' => env('BROWSER_CACHE_ROUTE_PREFIX', 'browser-cache'),
    'admin' => [
        'enabled' => (bool) env('BROWSER_CACHE_ADMIN_ENABLED', false),
        'middleware' => ['web', 'auth', 'can:manage-browser-cache'],
    ],
    'widget' => [
        'enabled' => true,
        'position' => 'bottom-right',
        'offset_x' => 20,
        'offset_y' => 20,
    ],
    // All cleanup options default off. Only app-owned cache names/databases may be listed.
    'recovery' => [
        'enabled' => false,
        // Sends Clear-Site-Data: "cache" on the manual action; clears the origin's browser cache.
        'clear_http_cache' => false,
        'clear_cache_storage' => false,
        'cache_storage_names' => [],
        'unregister_service_workers' => false,
        'service_worker_scopes' => [],
        'delete_indexeddb' => false,
        'indexeddb_names' => [],
        'clear_local_storage' => false,
        'local_storage_prefixes' => [],
        'reload_once' => true,
    ],
];

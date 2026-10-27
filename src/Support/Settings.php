<?php

namespace Aanggakrishna\BrowserCacheManager\Support;

use Aanggakrishna\BrowserCacheManager\Models\BrowserCacheSetting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    private const CACHE_KEY = 'browser-cache-manager.settings.v1';

    public function all(): array
    {
        $defaults = [
            'enabled' => true,
            'auto_check' => true,
            'recovery_enabled' => (bool) config('browser-cache-manager.recovery.enabled', false),
            'force_reload' => false,
            'clear_cache_storage' => (bool) config('browser-cache-manager.recovery.clear_cache_storage', false),
            'unregister_service_workers' => (bool) config('browser-cache-manager.recovery.unregister_service_workers', false),
            'delete_indexeddb' => (bool) config('browser-cache-manager.recovery.delete_indexeddb', false),
            'clear_local_storage' => (bool) config('browser-cache-manager.recovery.clear_local_storage', false),
        ];

        if (!config('browser-cache-manager.enabled', true)) {
            return array_replace($defaults, ['enabled' => false]);
        }

        $ttl = max(1, (int) config('browser-cache-manager.settings_cache_seconds', 15));
        $values = Cache::remember(self::CACHE_KEY, $ttl, static function (): array {
            try {
                return BrowserCacheSetting::query()->pluck('value', 'key')->all();
            } catch (\Throwable) {
                // Before migration (or during DB outage), use safe configured defaults.
                return [];
            }
        });

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $defaults)) {
                $defaults[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $defaults;
    }

    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            BrowserCacheSetting::query()->updateOrCreate(['key' => $key], ['value' => $value ? '1' : '0']);
        }
        Cache::forget(self::CACHE_KEY);
    }
}

# Laravel Browser Cache Manager

A Laravel package that helps browsers detect when your application has been updated and, when an administrator allows it, clear selected browser storage. It also provides a small floating button so users can start recovery manually.

> **Important:** Websites cannot guarantee that a browser will clear its entire HTTP cache. A `301` redirect stored in the browser's disk cache may still be used. This package cannot guarantee that such a redirect is removed.

## Quick start

### 1. Install the package

The package supports PHP 8.1+ and Laravel 10, 11, or 12.

```bash
composer require aanggakrishna/laravel-browser-cache-manager
```

Laravel discovers the service provider automatically. Publish the configuration and JavaScript file, then run the migration:

```bash
php artisan vendor:publish --tag=browser-cache-config
php artisan vendor:publish --tag=browser-cache-assets
php artisan migrate
```

The configuration is at `config/browser-cache-manager.php`. The JavaScript file is published to `public/vendor/browser-cache-manager/`.

### 2. Show the button

Add this directive once to your main Blade layout, usually inside `<head>`:

```blade
@browserCacheManager
```

A small tool button appears in the bottom-right corner. Users can open it and choose **Clear cache & update**. When the operation finishes, the package shows a results summary and a separate button to reload the application.

### 3. Set the application build version

Use a version identifier that changes whenever you release a new application build. Set it in your deployment environment:

```env
BROWSER_CACHE_MANAGER_ENABLED=true
APP_BUILD_VERSION=2026.10.27.1
```

Use the same `APP_BUILD_VERSION` on every server or instance running that build. Change the value for the next build. After changing the environment, follow your normal deployment process and rebuild the config cache if your application uses it:

```bash
php artisan config:cache
```

The package compares build versions to detect updates. Using one version across all instances prevents browsers from receiving alternating versions and getting stuck in a reload loop.

> **About the bump command:** `php artisan browser-cache:bump <version>` updates `APP_BUILD_VERSION` in a writable `.env` file. It is useful for installations that manage a local `.env` file. If your deployment gets environment values from a platform or secret manager, update the version there instead.

For example:

```bash
php artisan browser-cache:bump 2026.10.27.2
```

## Configure the button

By default, the button is enabled in the bottom-right corner, 20 pixels from the edges. Change the `widget` section in `config/browser-cache-manager.php`:

```php
'widget' => [
    'enabled' => true,
    'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
    'offset_x' => 20,
    'offset_y' => 20,
],
```

Set `enabled` to `false` to hide the button. You can also change its position and offsets from the admin page if you enable that page.

## Enable cache recovery

Cleanup is disabled by default so the package does not remove browser storage without your application's explicit configuration. To enable it, set the `recovery` options in `config/browser-cache-manager.php`.

This example only removes the Cache Storage entry named `my-app-v3` and localStorage keys beginning with `my-app:`:

```php
'recovery' => [
    'enabled' => true,
    'clear_http_cache' => false,
    'clear_cache_storage' => true,
    'cache_storage_names' => ['my-app-v3'],
    'unregister_service_workers' => false,
    'service_worker_scopes' => [],
    'delete_indexeddb' => false,
    'indexeddb_names' => [],
    'clear_local_storage' => true,
    'local_storage_prefixes' => ['my-app:'],
    'reload_once' => true,
],
```

Only list cache names, database names, service worker scopes, and localStorage prefixes owned by your application. Avoid clearing all origin data if other applications or systems share that storage.

Enable these options individually as needed:

- `clear_cache_storage`: remove the configured Cache Storage entries.
- `unregister_service_workers`: unregister service workers whose scopes are configured.
- `delete_indexeddb`: delete the configured IndexedDB databases.
- `clear_local_storage`: remove localStorage keys that start with the configured prefixes.
- `clear_http_cache`: send the `Clear-Site-Data: "cache"` header when the recovery endpoint is called.

Browser operations are best effort. The button reports results JavaScript can observe, such as how many entries were removed or which operations the browser reported as failed. The summary does not prove that every browser cache was cleared.

### HTTP cache and `301` redirects

Page JavaScript cannot clear the browser's entire HTTP cache. If `clear_http_cache` is enabled, the server asks the browser to clear cached data for this origin using the `Clear-Site-Data: "cache"` header. Browser support and behavior vary. This request may clear other cached responses for the same origin, and it does not clear CDN or proxy caches.

A browser may also use a locally stored `301` redirect without contacting the server. In that case, the server never receives the recovery request, so the package cannot remove that redirect. The user may need to clear site data or cache through the browser's settings.

This feature does not delete cookies or login sessions. Still, test the recovery configuration in the browsers your application supports before enabling broader cleanup options.

## Enable the admin page (optional)

The admin page is disabled by default. When enabled, it is available at `/browser-cache/admin` and uses the `web`, `auth`, and `can:manage-browser-cache` middleware by default.

Define the Gate in `App\Providers\AppServiceProvider` or your application's authorization provider:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('manage-browser-cache', function ($user): bool {
        return (bool) $user->is_admin;
    });
}
```

Replace `is_admin` with your application's authorization logic. Then enable the admin page:

```env
BROWSER_CACHE_ADMIN_ENABLED=true
```

You can change the middleware in `config/browser-cache-manager.php`:

```php
'admin' => [
    'enabled' => (bool) env('BROWSER_CACHE_ADMIN_ENABLED', false),
    'middleware' => ['web', 'auth', 'can:manage-browser-cache'],
],
```

Do not expose the admin route without appropriate authentication and authorization. This page changes runtime settings stored in the database.

## How polling works

The browser checks the status endpoint periodically for the build version and runtime settings. The default interval is 300 seconds; change it with `BROWSER_CACHE_POLL_INTERVAL`. The status endpoint is throttled and uses cache validators to keep repeated checks lightweight.

Runtime settings are stored in the `browser_cache_settings` table. The `BROWSER_CACHE_MANAGER_ENABLED=false` environment master switch can disable the package for a deployment without relying on the database.

## Run the tests

```bash
composer install
composer test
```

## License

MIT. See [LICENSE](LICENSE).

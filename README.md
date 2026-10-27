# Laravel Browser Cache Manager

Safe, opt-in build version polling for Laravel 10, 11, and 12 applications. It can notify the browser that a new build is deployed, provide a floating repair button, and—only when explicitly enabled—remove selected app-owned browser resources.

## Important browser limits

A web server cannot remotely clear a user's entire browser cache on demand in every browser. JavaScript runs under the current origin and browser permission model. Cache Storage, service workers, IndexedDB, and localStorage can be managed only where supported and only for resources belonging to that origin. This package does not touch cookies, session storage, authentication state, or unrelated storage by default. Recovery is disabled by default. Clearing or unregistering resources may disrupt an application if configured incorrectly; keep explicit allowlists narrow and test them first. There is no guarantee every stale asset or CDN response can be cleared.

The optional `Clear-Site-Data: "cache"` response can ask a supporting browser to clear its HTTP cache for the whole origin. It only works when the browser reaches the server; it cannot fix a 301 redirect already served directly from browser cache. It does not clear CDN/proxy caches and can increase reload traffic across the site, so it is off by default.

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- A cache store configured by Laravel (the database store is supported)
- Composer 2

## Install

After publishing this repository under GitHub account `aanggakrishna`, create the Packagist package from the GitHub repository and install it in an application:

```bash
composer require aanggakrishna/laravel-browser-cache-manager
php artisan vendor:publish --tag=browser-cache-config
php artisan vendor:publish --tag=browser-cache-assets
php artisan migrate
```

Auto-discovery registers the service provider. Publish the JS asset again when upgrading the package. Configure a stable release/build ID that is identical on every application instance:

```dotenv
BROWSER_CACHE_MANAGER_ENABLED=true
APP_BUILD_VERSION=2026.10.27.1
BROWSER_CACHE_POLL_INTERVAL=300
```

`APP_BUILD_VERSION` is the sole version source returned by the endpoint. Keep it in sync with the deployed frontend assets and across all nodes. Use a release ID (git SHA or immutable build number) rather than a value generated separately on each server. Do not derive it from request time. If Laravel config is cached, rebuild the config cache as part of deployment.

## Blade integration

Put the directive in the main layout, preferably in `<head>`:

```blade
@browserCacheManager
```

This loads the published `public/vendor/browser-cache-manager/browser-cache-manager.js` and polls the named status route. If the package master switch is off, the directive renders nothing. The endpoint returns `ETag` and supports conditional requests; Laravel's cache store holds the database settings for a short configurable period to reduce repeated reads across many clients.

## Controls

The `BROWSER_CACHE_MANAGER_ENABLED` environment value is a deployment-level master switch. When true, operators can change the runtime `enabled` and `auto_check` settings in `browser_cache_settings` without redeploying. DB settings are cached for 15 seconds by default (`BROWSER_CACHE_SETTINGS_CACHE_SECONDS`); allow for that propagation delay. A database/cache outage falls back to safe defaults, and the endpoint still returns the configured build version.

The floating tool button is enabled by default and appears at the bottom-right. Choose one of `bottom-right`, `bottom-left`, `top-right`, or `top-left`, and offsets from 0–500 pixels, in `config/browser-cache-manager.php` or in the optional admin UI. Its menu action reports deletion counts, missing resources, and failed/blocked operations before offering a separate reload button. For the HTTP cache, it reports only that the browser received a clear request; web pages cannot confirm that each browser actually cleared it. For the HTTP cache request to pass Laravel CSRF protection, add this to the host layout's `<head>`:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

The recovery configuration is opt-in and narrowly scoped:

```php
// config/browser-cache-manager.php
'recovery' => [
    'enabled' => false,
    'clear_http_cache' => false,
    'clear_cache_storage' => false,
    'cache_storage_names' => ['my-app-cache-v1'],
    'unregister_service_workers' => false,
    'service_worker_scopes' => ['/app/'],
    'delete_indexeddb' => false,
    'indexeddb_names' => ['my-app-db'],
    'clear_local_storage' => false,
    'local_storage_prefixes' => ['myapp:'],
    'reload_once' => true,
],
```

The database `recovery_enabled` switch must also be enabled for destructive recovery to run. Resource names/scopes/prefixes are always supplied by deployment config, never by an HTTP request or admin form. Service worker unregistering is limited to configured scope prefixes. Local storage cleanup removes only keys beginning with configured prefixes. The client records the build identifier and avoids repeating recovery for the same build; reload-once also uses session storage to avoid a reload loop. `force_reload` defaults off. Test upgrades with representative browser profiles before enabling cleanup.

## Optional admin UI

The admin UI is off by default. Enable it in `config/browser-cache-manager.php`:

```php
'admin' => [
    'enabled' => env('BROWSER_CACHE_ADMIN_ENABLED', false),
    'middleware' => ['web', 'auth', 'can:manage-browser-cache'],
],
```

Define authorization in the host application, for example in `App\Providers\AppServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('manage-browser-cache', fn ($user) => $user->is_admin);
```

The settings UI is at `/browser-cache/admin` by default and uses authenticated web middleware, authorization, CSRF protection, and validation. Customize `route_prefix` and the middleware list for your app. Do not remove the authorization middleware on a public deployment.

## Version command

```bash
php artisan browser-cache:bump 2026.10.27.2
```

This updates `APP_BUILD_VERSION` in the app's writable `.env`. In immutable/container deployments, set the variable in the release environment instead. Run the command before `php artisan config:cache`, then deploy the same version and assets to every node. The command does not change package code or database state.

## Polling behavior and scaling

Default interval is five minutes, clamped by the endpoint to 30–3600 seconds. The endpoint response is small and conditionally cached using an ETag. The setting table is read once per application cache TTL rather than once per poll. Apply an appropriate Laravel throttle for your traffic and use a shared cache backend in multi-node deployments if you need immediate, consistent setting propagation. Browsers that are closed, offline, or suspended will check on a later visit; the package does not push updates to dormant clients.

## Tests and local development

```bash
composer install
composer test
```

For a local consumer app, use a Composer path repository or a temporary VCS repository. Do not commit credentials or `.env` files.

## GitHub and Packagist publishing

1. Create a public GitHub repository named `laravel-browser-cache-manager` under `aanggakrishna` and push this source.
2. Ensure CI passes on the default branch.
3. Create a signed/reviewed version tag, for example `v1.0.0`, and push the tag.
4. On Packagist, submit `https://github.com/aanggakrishna/laravel-browser-cache-manager` as a new package. Configure the GitHub service hook or Packagist GitHub integration so new tags update automatically.
5. Verify Packagist reports package name `aanggakrishna/laravel-browser-cache-manager`, the tag, and the intended PHP/Laravel constraints. Test installation in a clean Laravel app before announcing a release.

This workspace contains source only; it has not created a GitHub repository or published to Packagist.

## License

MIT. See [LICENSE](LICENSE).

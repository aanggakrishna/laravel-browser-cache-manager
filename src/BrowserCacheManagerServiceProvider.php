<?php

namespace Aanggakrishna\BrowserCacheManager;

use Aanggakrishna\BrowserCacheManager\Console\BumpVersionCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BrowserCacheManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/browser-cache-manager.php', 'browser-cache-manager');
        $this->app->singleton(Support\Settings::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-cache-manager');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Blade::directive('browserCacheManager', function ($expression = null): string {
            if (!config('browser-cache-manager.enabled', true)) return '';
            $url = route('browser-cache-manager.status');
            $recoveryUrl = route('browser-cache-manager.recover');
            $script = asset('vendor/browser-cache-manager/browser-cache-manager.js');
            $widget = (array) config('browser-cache-manager.widget', []);
            $widgetEnabled = ($widget['enabled'] ?? true) ? 'true' : 'false';
            return '<script src="'.e($script).'" data-endpoint="'.e($url).'" data-recovery-endpoint="'.e($recoveryUrl).'" data-widget-enabled="'.$widgetEnabled.'" data-widget-position="'.e((string) ($widget['position'] ?? 'bottom-right')).'" data-widget-offset-x="'.e((string) ($widget['offset_x'] ?? 20)).'" data-widget-offset-y="'.e((string) ($widget['offset_y'] ?? 20)).'" defer></script>';
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/browser-cache-manager.php' => config_path('browser-cache-manager.php')], 'browser-cache-config');
            $this->publishes([__DIR__.'/../resources/js/browser-cache-manager.js' => public_path('vendor/browser-cache-manager/browser-cache-manager.js')], 'browser-cache-assets');
            $this->commands([BumpVersionCommand::class]);
        }
    }
}

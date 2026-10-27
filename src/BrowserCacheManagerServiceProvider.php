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
            $script = asset('vendor/browser-cache-manager/browser-cache-manager.js');
            return '<script src="'.e($script).'" data-endpoint="'.e($url).'" defer></script>';
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/browser-cache-manager.php' => config_path('browser-cache-manager.php')], 'browser-cache-config');
            $this->publishes([__DIR__.'/../resources/js/browser-cache-manager.js' => public_path('vendor/browser-cache-manager/browser-cache-manager.js')], 'browser-cache-assets');
            $this->commands([BumpVersionCommand::class]);
        }
    }
}

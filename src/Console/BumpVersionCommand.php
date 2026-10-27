<?php

namespace Aanggakrishna\BrowserCacheManager\Console;

use Illuminate\Console\Command;

class BumpVersionCommand extends Command
{
    protected $signature = 'browser-cache:bump {version : Release identifier, e.g. 1.2.3+build.42}';
    protected $description = 'Set APP_BUILD_VERSION in the application .env file';

    public function handle(): int
    {
        $version = trim((string) $this->argument('version'));
        if ($version === '' || preg_match('/^[A-Za-z0-9][A-Za-z0-9._+-]{0,127}$/', $version) !== 1) {
            $this->error('Version must contain only letters, numbers, dots, underscores, plus signs, or hyphens.');
            return self::FAILURE;
        }

        $path = base_path('.env');
        if (!is_file($path) || !is_writable($path)) {
            $this->error('A writable application .env file is required. Set APP_BUILD_VERSION in your deployment environment instead.');
            return self::FAILURE;
        }

        $contents = file_get_contents($path);
        $line = 'APP_BUILD_VERSION='.$version;
        if (preg_match('/^APP_BUILD_VERSION=.*$/m', $contents)) {
            $contents = preg_replace('/^APP_BUILD_VERSION=.*$/m', $line, $contents, 1);
        } else {
            $contents = rtrim($contents)."\n".$line."\n";
        }
        file_put_contents($path, $contents, LOCK_EX);
        $this->info("APP_BUILD_VERSION set to {$version}. Rebuild config cache and deploy this same release identifier to every app instance.");

        return self::SUCCESS;
    }
}

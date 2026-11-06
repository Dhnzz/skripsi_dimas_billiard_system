<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') !== 'local' || request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        // Safeguard: Ensure Livewire compile storage directories exist and are writable
        $livewireCache = storage_path('framework/views/livewire');
        if (!is_dir($livewireCache)) {
            @mkdir($livewireCache, 0777, true);
        }
        @chmod($livewireCache, 0777);

        $subdirs = ['classes', 'views', 'scripts', 'styles', 'placeholders'];
        foreach ($subdirs as $sub) {
            $dir = $livewireCache . DIRECTORY_SEPARATOR . $sub;
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            @chmod($dir, 0777);
        }

        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            @chown($livewireCache, 'www-data');
            @chgrp($livewireCache, 'www-data');
            foreach ($subdirs as $sub) {
                $dir = $livewireCache . DIRECTORY_SEPARATOR . $sub;
                @chown($dir, 'www-data');
                @chgrp($dir, 'www-data');
                @chmod($dir, 0777);
            }
        }
    }
}

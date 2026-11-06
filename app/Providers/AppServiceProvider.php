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

        foreach (['classes', 'views', 'scripts', 'styles', 'placeholders'] as $sub) {
            $dir = $livewireCache . DIRECTORY_SEPARATOR . $sub;
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (is_dir($dir) && !is_writable($dir)) {
                @chmod($dir, 0777);
            }
        }
    }
}

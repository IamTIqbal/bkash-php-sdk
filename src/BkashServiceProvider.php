<?php
namespace Bkash;

use Illuminate\Support\ServiceProvider;

class BkashServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/bkash.php', 'bkash');
        $this->app->singleton(BkashClient::class, function ($app) {
            $config = config('bkash');
            return new BkashClient($config, $config['debug'] ?? false);
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/bkash.php' => config_path('bkash.php'),
        ], 'config');
    }
}

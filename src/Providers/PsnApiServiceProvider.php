<?php

namespace Hawkiq\LaravelPsnApi\Providers;

use Hawkiq\LaravelPsnApi\Auth\PsnAuthenticator;
use Hawkiq\LaravelPsnApi\Resolvers\TitleResolver;
use Hawkiq\LaravelPsnApi\Services\PlayStationTrophyService;
use Illuminate\Support\ServiceProvider;

class PsnApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/psnapi.php', 'psnapi');

        $this->app->singleton(PsnAuthenticator::class, function ($app) {
            return new PsnAuthenticator($app['config']['psnapi']);
        });

        $this->app->singleton('psnapi', function ($app) {
            return new PlayStationTrophyService(
                $app->make(PsnAuthenticator::class),
                $app->make(TitleResolver::class)
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/psnapi.php' => config_path('psnapi.php'),
            ], 'psnapi-config');
        }
    }
}

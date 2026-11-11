<?php

declare(strict_types=1);

namespace LaravelSzamlazz\LaravelSzamlazz;

use Illuminate\Support\ServiceProvider;
use LaravelSzamlazz\LaravelSzamlazz\Console\Commands\LaravelSzamlazzCommand;

class LaravelSzamlazzServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-szamlazz.php', 'laravel-szamlazz');

        $this->app->singleton(LaravelSzamlazz::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/laravel-szamlazz.php');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-szamlazz.php' => config_path('laravel-szamlazz.php'),
        ], ['laravel-szamlazz', 'laravel-szamlazz-config']);

        $this->commands([
            LaravelSzamlazzCommand::class,
        ]);
    }
}

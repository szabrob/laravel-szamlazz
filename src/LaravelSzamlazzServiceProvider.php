<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Szabrob\LaravelSzamlazz\Data\Credentials;

class LaravelSzamlazzServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-szamlazz.php', 'laravel-szamlazz');

        $this->app->singleton(SzamlaAgent::class, function (Application $app): SzamlaAgent {
            $config = $app->make('config');

            return new SzamlaAgent(
                http: $app->make(HttpFactory::class),
                credentials: new Credentials(
                    apiKey: $this->nullableString($config->get('laravel-szamlazz.api_key')),
                    username: $this->nullableString($config->get('laravel-szamlazz.username')),
                    password: $this->nullableString($config->get('laravel-szamlazz.password')),
                ),
                baseUrl: $this->nullableString($config->get('laravel-szamlazz.base_url')) ?? SzamlaAgent::DEFAULT_BASE_URL,
                timeout: (int) $config->get('laravel-szamlazz.timeout', 30),
                eInvoice: (bool) $config->get('laravel-szamlazz.e_invoice', true),
                downloadPdf: (bool) $config->get('laravel-szamlazz.download_pdf', true),
                aggregator: $this->nullableString($config->get('laravel-szamlazz.aggregator')),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-szamlazz.php' => config_path('laravel-szamlazz.php'),
        ], ['laravel-szamlazz', 'laravel-szamlazz-config']);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}

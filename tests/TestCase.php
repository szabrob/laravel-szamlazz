<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Szabrob\LaravelSzamlazz\LaravelSzamlazzServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            LaravelSzamlazzServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-szamlazz.api_key', 'test-agent-key');
    }
}

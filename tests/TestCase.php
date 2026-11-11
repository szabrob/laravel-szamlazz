<?php

declare(strict_types=1);

namespace LaravelSzamlazz\LaravelSzamlazz\Tests;

use LaravelSzamlazz\LaravelSzamlazz\LaravelSzamlazzServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelSzamlazzServiceProvider::class,
        ];
    }
}

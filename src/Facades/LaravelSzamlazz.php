<?php

declare(strict_types=1);

namespace LaravelSzamlazz\LaravelSzamlazz\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LaravelSzamlazz\LaravelSzamlazz\LaravelSzamlazz
 */
class LaravelSzamlazz extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LaravelSzamlazz\LaravelSzamlazz\LaravelSzamlazz::class;
    }
}

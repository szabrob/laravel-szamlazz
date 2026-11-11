<?php

declare(strict_types=1);

use LaravelSzamlazz\LaravelSzamlazz\LaravelSzamlazz;

it('resolves the singleton', function () {
    expect(app(LaravelSzamlazz::class))->toBeInstanceOf(LaravelSzamlazz::class);
});

it('returns the same instance from the container', function () {
    expect(app(LaravelSzamlazz::class))->toBe(app(LaravelSzamlazz::class));
});

it('merges the package config', function () {
    expect(config('laravel-szamlazz.placeholder'))->toBe('default');
});

it('registers the artisan command', function () {
    $this->artisan('laravel-szamlazz:placeholder')
        ->expectsOutputToContain('LaravelSzamlazz placeholder command executed.')
        ->assertSuccessful();
});

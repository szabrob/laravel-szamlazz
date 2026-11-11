<?php

declare(strict_types=1);

use Szabrob\LaravelSzamlazz\Facades\SzamlaAgent as SzamlaAgentFacade;
use Szabrob\LaravelSzamlazz\SzamlaAgent;

it('registers the Számla Agent client as a singleton', function () {
    expect(app(SzamlaAgent::class))
        ->toBeInstanceOf(SzamlaAgent::class)
        ->toBe(app(SzamlaAgent::class));
});

it('resolves the facade to the client', function () {
    expect(SzamlaAgentFacade::getFacadeRoot())->toBe(app(SzamlaAgent::class));
});

it('merges the package config', function () {
    expect(config('laravel-szamlazz.base_url'))->toBe('https://www.szamlazz.hu/szamla/')
        ->and(config('laravel-szamlazz.timeout'))->toBe(30)
        ->and(config('laravel-szamlazz.e_invoice'))->toBeTrue()
        ->and(config('laravel-szamlazz.download_pdf'))->toBeTrue();
});

it('publishes the config file', function () {
    $target = config_path('laravel-szamlazz.php');
    @unlink($target);

    $this->artisan('vendor:publish', ['--tag' => 'laravel-szamlazz-config'])->assertSuccessful();

    expect($target)->toBeFile();

    unlink($target);
});

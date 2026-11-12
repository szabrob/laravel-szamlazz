<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptEmail;
use Szabrob\LaravelSzamlazz\Facades\SzamlaAgent as SzamlaAgentFacade;
use Szabrob\LaravelSzamlazz\LaravelSzamlazzServiceProvider;
use Szabrob\LaravelSzamlazz\SzamlaAgent;

it('registers the Számla Agent client', function () {
    expect(app(SzamlaAgent::class))->toBeInstanceOf(SzamlaAgent::class);
});

it('resolves the facade to the client', function () {
    expect(SzamlaAgentFacade::getFacadeRoot())->toBeInstanceOf(SzamlaAgent::class);
});

it('uses config changes made after the client was first resolved', function () {
    $ok = Http::response('<?xml version="1.0" encoding="UTF-8"?><xmlnyugtasendvalasz xmlns="http://www.szamlazz.hu/xmlnyugtasendvalasz"><sikeres>true</sikeres></xmlnyugtasendvalasz>');
    Http::fake([AGENT_URL => $ok, 'agent.example.test/*' => $ok]);
    $email = new ReceiptEmail('NYGT-2026-1', 'buyer@example.com');

    SzamlaAgentFacade::sendReceipt($email);
    app(SzamlaAgent::class)->sendReceipt($email);

    config()->set('laravel-szamlazz.api_key', 'tenant-b-key');
    config()->set('laravel-szamlazz.base_url', 'https://agent.example.test/szamla/');

    SzamlaAgentFacade::sendReceipt($email);
    app(SzamlaAgent::class)->sendReceipt($email);

    $keys = Http::recorded()->map(function (array $pair): string {
        [$request] = $pair;

        return $request->url().' '.(preg_match('/<szamlaagentkulcs>(.*)<\/szamlaagentkulcs>/', sentXml($request, 'action-szamla_agent_nyugta_send'), $m) ? $m[1] : '');
    })->all();

    expect($keys)->toBe([
        'https://www.szamlazz.hu/szamla/ test-agent-key',
        'https://www.szamlazz.hu/szamla/ test-agent-key',
        'https://agent.example.test/szamla/ tenant-b-key',
        'https://agent.example.test/szamla/ tenant-b-key',
    ]);
});

it('merges the package config', function () {
    expect(config('laravel-szamlazz.base_url'))->toBe('https://www.szamlazz.hu/szamla/')
        ->and(config('laravel-szamlazz.timeout'))->toBe(30)
        ->and(config('laravel-szamlazz.e_invoice'))->toBeTrue()
        ->and(config('laravel-szamlazz.download_pdf'))->toBeTrue();
});

it('registers the config file for publishing', function () {
    // Asserting the publish map instead of running vendor:publish keeps parallel
    // workers from racing on the shared Testbench skeleton config directory.
    $source = realpath(__DIR__.'/../../config/laravel-szamlazz.php');

    foreach (['laravel-szamlazz', 'laravel-szamlazz-config'] as $tag) {
        $paths = ServiceProvider::pathsToPublish(LaravelSzamlazzServiceProvider::class, $tag);

        expect(array_map(realpath(...), array_keys($paths)))->toBe([$source])
            ->and(array_values($paths))->toBe([config_path('laravel-szamlazz.php')]);
    }
});

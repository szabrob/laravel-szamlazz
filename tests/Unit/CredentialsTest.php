<?php

declare(strict_types=1);

use Szabrob\LaravelSzamlazz\Data\Credentials;

it('hides the agent key and password from array and json output', function () {
    $credentials = new Credentials(apiKey: 'secret-agent-key', username: 'demo', password: 'secret-password');

    expect($credentials->toArray())->toBe(['username' => 'demo'])
        ->and($credentials->toJson())
        ->not->toContain('secret-agent-key')
        ->not->toContain('secret-password');
});

it('keeps the hidden values available to the client', function () {
    $credentials = Credentials::fromLogin('demo', 'secret-password');

    expect($credentials->password)->toBe('secret-password')
        ->and($credentials->isComplete())->toBeTrue()
        ->and(Credentials::fromApiKey('secret-agent-key')->apiKey)->toBe('secret-agent-key');
});

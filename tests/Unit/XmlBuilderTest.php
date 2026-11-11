<?php

declare(strict_types=1);

use Szabrob\LaravelSzamlazz\Support\Amounts;
use Szabrob\LaravelSzamlazz\Support\XmlBuilder;

it('skips null values, repeats lists and keeps element order', function () {
    $xml = XmlBuilder::build('root', 'urn:test', [
        'a' => 'x',
        'skip' => null,
        'flag' => false,
        'number' => 1.5,
        'empty' => [],
        'item' => [['n' => 1], ['n' => 2]],
    ]);

    expect($xml)
        ->toContain('<root xmlns="urn:test">')
        ->toContain('<a>x</a>')
        ->not->toContain('skip')
        ->toContain('<flag>false</flag>')
        ->toContain('<number>1.5</number>')
        ->toContain('<empty/>')
        ->toMatch('/<item>\s*<n>1<\/n>\s*<\/item>\s*<item>\s*<n>2<\/n>\s*<\/item>/');
});

it('calculates missing line item amounts', function () {
    expect(Amounts::resolve(3, 333.33, '27'))->toBe(['net' => 999.99, 'vat' => 270.0, 'gross' => 1269.99])
        ->and(Amounts::resolve(1, 100, 'TAM'))->toBe(['net' => 100.0, 'vat' => 0.0, 'gross' => 100.0])
        ->and(Amounts::resolve(1, 100, '27', vat: 30))->toBe(['net' => 100.0, 'vat' => 30.0, 'gross' => 130.0]);
});

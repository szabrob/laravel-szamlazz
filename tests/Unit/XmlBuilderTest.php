<?php

declare(strict_types=1);

use Szabrob\LaravelSzamlazz\Exceptions\InvalidSzamlazzRequest;
use Szabrob\LaravelSzamlazz\Support\Amounts;
use Szabrob\LaravelSzamlazz\Support\XmlBuilder;

it('skips null values, repeats lists and keeps element order', function () {
    $xml = XmlBuilder::build('root', 'urn:test', [
        'a' => 'x',
        'skip' => null,
        'flag' => false,
        'number' => 1.5,
        'emptyList' => [],
        'emptyGroup' => ['child' => null],
        'item' => [['n' => 1], ['n' => 2]],
    ]);

    expect($xml)
        ->toContain('<root xmlns="urn:test">')
        ->toContain('<a>x</a>')
        ->not->toContain('skip')
        ->toContain('<flag>false</flag>')
        ->toContain('<number>1.5</number>')
        ->not->toContain('emptyList')
        ->toContain('<emptyGroup/>')
        ->toMatch('/<item>\s*<n>1<\/n>\s*<\/item>\s*<item>\s*<n>2<\/n>\s*<\/item>/');
});

it('derives net and VAT from a gross only amount', function () {
    expect(Amounts::resolve(1, 100, '27', gross: 127))->toBe(['net' => 100.0, 'vat' => 27.0, 'gross' => 127.0])
        ->and(Amounts::resolve(1, 100, '27', gross: 130))->toBe(['net' => 102.36, 'vat' => 27.64, 'gross' => 130.0])
        ->and(Amounts::resolve(1, 100, 'AAM', gross: 130))->toBe(['net' => 130.0, 'vat' => 0.0, 'gross' => 130.0]);
});

it('derives the missing amount from the two given ones', function () {
    expect(Amounts::resolve(1, 100, '27', net: 100, gross: 130))->toBe(['net' => 100.0, 'vat' => 30.0, 'gross' => 130.0])
        ->and(Amounts::resolve(1, 100, '27', vat: 30, gross: 130))->toBe(['net' => 100.0, 'vat' => 30.0, 'gross' => 130.0]);
});

it('rejects three amounts that do not add up', function () {
    Amounts::resolve(1, 100, '27', net: 100, vat: 27, gross: 130, item: 'Tárhely');
})->throws(InvalidSzamlazzRequest::class, 'Inconsistent amounts for line item [Tárhely]: net 100.00 + VAT 27.00 does not equal gross 130.00.');

it('accepts three amounts that add up', function () {
    expect(Amounts::resolve(1, 100, '27', net: 100.1, vat: 27.03, gross: 127.13))
        ->toBe(['net' => 100.1, 'vat' => 27.03, 'gross' => 127.13]);
});

it('calculates missing line item amounts', function () {
    expect(Amounts::resolve(3, 333.33, '27'))->toBe(['net' => 999.99, 'vat' => 270.0, 'gross' => 1269.99])
        ->and(Amounts::resolve(1, 100, 'TAM'))->toBe(['net' => 100.0, 'vat' => 0.0, 'gross' => 100.0])
        ->and(Amounts::resolve(1, 100, '27', vat: 30))->toBe(['net' => 100.0, 'vat' => 30.0, 'gross' => 130.0]);
});

it('accepts dot decimal VAT percentages', function () {
    expect(Amounts::resolve(1, 100, '5.5'))->toBe(['net' => 100.0, 'vat' => 5.5, 'gross' => 105.5])
        ->and(Amounts::resolve(1, 100, '0'))->toBe(['net' => 100.0, 'vat' => 0.0, 'gross' => 100.0]);
});

it('treats every Számla Agent VAT key as zero VAT', function (string $key) {
    expect(Amounts::resolve(1, 100, $key))->toBe(['net' => 100.0, 'vat' => 0.0, 'gross' => 100.0]);
})->with(Amounts::VAT_KEYS);

it('rejects malformed VAT rates', function (string $vatRate) {
    Amounts::resolve(1, 100, $vatRate, item: 'Tárhely');
})->with(['27%', '27,0', '', ' 27', '-5', '1e1', 'aam', 'XYZ'])
    ->throws(InvalidSzamlazzRequest::class, 'for line item [Tárhely]');

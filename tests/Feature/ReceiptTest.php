<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Szabrob\LaravelSzamlazz\Data\Receipt\Receipt;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptEmail;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptLookup;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptReversal;
use Szabrob\LaravelSzamlazz\Data\Results\ReceiptResult;
use Szabrob\LaravelSzamlazz\Exceptions\SzamlazzException;
use Szabrob\LaravelSzamlazz\Facades\SzamlaAgent;

function fakeReceiptResponse(): void
{
    Http::fake([AGENT_URL => Http::response(file_get_contents(__DIR__.'/../Fixtures/responses/receipt.xml'))]);
}

it('creates a receipt', function () {
    fakeReceiptResponse();

    $result = SzamlaAgent::createReceipt(Receipt::from([
        'prefix' => 'NYGT',
        'callId' => 'order-42',
        'items' => [
            ['name' => 'Kávé', 'quantity' => 2, 'unit' => 'db', 'netUnitPrice' => 500, 'vatRate' => '27'],
        ],
        'payments' => [
            ['method' => 'készpénz', 'amount' => 1270],
        ],
    ]));

    expect($result)->toBeInstanceOf(ReceiptResult::class)
        ->receiptNumber->toBe('NYGT-2026-1')
        ->id->toBe(98765)
        ->callId->toBe('order-42')
        ->reversed->toBeFalse()
        ->grossTotal->toBe(1270.0)
        ->pdf->toBe('%PDF-1.4 test')
        ->and($result->issueDate?->format('Y-m-d'))->toBe('2026-11-11');

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_nyugta_create');
        expectValidAgainstXsd($xml, 'xmlnyugtacreate');

        return str_contains($xml, '<netto>1000</netto>')
            && str_contains($xml, '<afa>270</afa>')
            && str_contains($xml, '<brutto>1270</brutto>')
            && str_contains($xml, '<fizetoeszkoz>készpénz</fizetoeszkoz>');
    });
});

it('reverses a receipt', function () {
    fakeReceiptResponse();

    SzamlaAgent::reverseReceipt(new ReceiptReversal('NYGT-2026-1'));

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_nyugta_storno');
        expectValidAgainstXsd($xml, 'xmlnyugtast');

        return str_contains($xml, '<nyugtaszam>NYGT-2026-1</nyugtaszam>');
    });
});

it('queries a receipt', function () {
    fakeReceiptResponse();

    expect(SzamlaAgent::receipt(ReceiptLookup::byCallId('order-42'))->receiptNumber)->toBe('NYGT-2026-1');

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_nyugta_get');
        expectValidAgainstXsd($xml, 'xmlnyugtaget');

        return str_contains($xml, '<hivasAzonosito>order-42</hivasAzonosito>');
    });
});

it('rejects an empty receipt lookup', function () {
    SzamlaAgent::receipt(new ReceiptLookup);
})->throws(InvalidArgumentException::class);

it('emails a receipt', function () {
    Http::fake([AGENT_URL => Http::response(
        '<?xml version="1.0" encoding="UTF-8"?><xmlnyugtasendvalasz xmlns="http://www.szamlazz.hu/xmlnyugtasendvalasz"><sikeres>true</sikeres></xmlnyugtasendvalasz>',
    )]);

    SzamlaAgent::sendReceipt(new ReceiptEmail('NYGT-2026-1', 'buyer@example.com', subject: 'Nyugta'));

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_nyugta_send');
        expectValidAgainstXsd($xml, 'xmlnyugtasend');

        return str_contains($xml, '<email>buyer@example.com</email>');
    });
});

it('throws on an unsuccessful receipt response', function () {
    Http::fake([AGENT_URL => Http::response(
        '<?xml version="1.0" encoding="UTF-8"?><xmlnyugtavalasz xmlns="http://www.szamlazz.hu/xmlnyugtavalasz"><sikeres>false</sikeres><hibakod>338</hibakod><hibauzenet>Nem létező nyugta.</hibauzenet></xmlnyugtavalasz>',
    )]);

    SzamlaAgent::receipt(ReceiptLookup::byReceiptNumber('NOPE'));
})->throws(SzamlazzException::class, 'Számla Agent error [338]: Nem létező nyugta.');

<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Szabrob\LaravelSzamlazz\Data\Credentials;
use Szabrob\LaravelSzamlazz\Data\Invoice\Invoice;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceLookup;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceReversal;
use Szabrob\LaravelSzamlazz\Data\Invoice\PaymentRegistration;
use Szabrob\LaravelSzamlazz\Data\Results\InvoiceDetails;
use Szabrob\LaravelSzamlazz\Data\Results\InvoiceResult;
use Szabrob\LaravelSzamlazz\Enums\InvoiceType;
use Szabrob\LaravelSzamlazz\Enums\Language;
use Szabrob\LaravelSzamlazz\Enums\TaxPayerType;
use Szabrob\LaravelSzamlazz\Exceptions\InvalidSzamlazzRequest;
use Szabrob\LaravelSzamlazz\Exceptions\SzamlazzException;
use Szabrob\LaravelSzamlazz\Facades\SzamlaAgent;

function invoice(array $overrides = []): Invoice
{
    return Invoice::from([
        'fulfillmentDate' => '2026-11-10',
        'dueDate' => '2026-11-19',
        'issueDate' => '2026-11-11',
        'language' => 'hu',
        'orderNumber' => 'ORDER-42',
        'comment' => 'Köszönjük a vásárlást & a bizalmat!',
        'seller' => [
            'bank' => 'OTP',
            'bankAccount' => '11111111-22222222-33333333',
        ],
        'buyer' => [
            'name' => 'Kovács Bt.',
            'postCode' => '2030',
            'city' => 'Érd',
            'address' => 'Tárnoki út 23.',
            'email' => 'buyer@example.com',
            'taxPayerType' => 1,
            'taxNumber' => '12345678-1-42',
        ],
        'items' => [
            ['name' => 'Webfejlesztés', 'quantity' => 2, 'unit' => 'óra', 'netUnitPrice' => 10000, 'vatRate' => '27'],
            ['name' => 'Tárhely', 'quantity' => 1, 'unit' => 'db', 'netUnitPrice' => 0, 'vatRate' => 'AAM'],
        ],
        ...$overrides,
    ]);
}

it('creates an invoice from typed data and returns a typed result', function () {
    Http::fake([AGENT_URL => Http::response(file_get_contents(__DIR__.'/../Fixtures/responses/invoice.xml'), 200, [
        'szlahu_id' => '123456',
        'szlahu_szamlaszam' => 'E-TEST-2026-1',
    ])]);

    $result = SzamlaAgent::createInvoice(invoice());

    expect($result)->toBeInstanceOf(InvoiceResult::class)
        ->invoiceNumber->toBe('E-TEST-2026-1')
        ->id->toBe(123456)
        ->netTotal->toBe(20000.0)
        ->grossTotal->toBe(25400.0)
        ->outstandingAmount->toBe(25400.0)
        ->customerAccountUrl->toBe('https://www.szamlazz.hu/szamla/?partguid=abc')
        ->pdf->toBe('%PDF-1.4 test')
        ->and($result->hasPdf())->toBeTrue()
        ->and($result->toArray())->not->toHaveKey('pdf');

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-xmlagentxmlfile');

        expectValidAgainstXsd($xml, 'xmlszamla');

        expect($request->url())->toBe(AGENT_URL)
            ->and($request->method())->toBe('POST')
            ->and($request->isMultipart())->toBeTrue()
            ->and($xml)
            ->toContain('<szamlaagentkulcs>test-agent-key</szamlaagentkulcs>')
            ->toContain('<valaszVerzio>2</valaszVerzio>')
            ->toContain('<keltDatum>2026-11-11</keltDatum>')
            ->toContain('<teljesitesDatum>2026-11-10</teljesitesDatum>')
            ->toContain('<megjegyzes>Köszönjük a vásárlást &amp; a bizalmat!</megjegyzes>')
            ->toContain('<adoalany>1</adoalany>')
            ->toContain('<nettoErtek>20000</nettoErtek>')
            ->toContain('<afaErtek>5400</afaErtek>')
            ->toContain('<bruttoErtek>25400</bruttoErtek>')
            ->toContain('<afakulcs>AAM</afakulcs>')
            ->not->toContain('<dijbekero>');

        return true;
    });
});

it('sends consistent amounts for a gross priced item', function () {
    Http::fake([AGENT_URL => Http::response(file_get_contents(__DIR__.'/../Fixtures/responses/invoice.xml'))]);

    SzamlaAgent::createInvoice(invoice(['items' => [
        ['name' => 'Bérlet', 'quantity' => 1, 'unit' => 'db', 'netUnitPrice' => 10236.22, 'vatRate' => '27', 'grossAmount' => 13000],
    ]]));

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-xmlagentxmlfile');
        expectValidAgainstXsd($xml, 'xmlszamla');

        return str_contains($xml, '<nettoErtek>10236.22</nettoErtek>')
            && str_contains($xml, '<afaErtek>2763.78</afaErtek>')
            && str_contains($xml, '<bruttoErtek>13000</bruttoErtek>');
    });
});

it('places the margin scheme base before the item amounts', function () {
    Http::fake([AGENT_URL => Http::response(file_get_contents(__DIR__.'/../Fixtures/responses/invoice.xml'))]);

    SzamlaAgent::createInvoice(invoice(['marginScheme' => true, 'items' => [
        ['name' => 'Használt autó', 'quantity' => 1, 'unit' => 'db', 'netUnitPrice' => 100, 'vatRate' => '27', 'marginSchemeBase' => 50],
    ]]));

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-xmlagentxmlfile');
        expectValidAgainstXsd($xml, 'xmlszamla');

        return (bool) preg_match('/<afakulcs>27<\/afakulcs>\s*<arresAfaAlap>50<\/arresAfaAlap>\s*<nettoErtek>100<\/nettoErtek>/', $xml);
    });
});

it('rejects inconsistent item amounts before sending', function () {
    Http::fake();

    expect(fn () => SzamlaAgent::createInvoice(invoice(['items' => [
        ['name' => 'Tárhely', 'quantity' => 1, 'unit' => 'db', 'netUnitPrice' => 100, 'vatRate' => '27', 'netAmount' => 100, 'vatAmount' => 27, 'grossAmount' => 130],
    ]])))->toThrow(InvalidSzamlazzRequest::class, 'line item [Tárhely]');

    Http::assertNothingSent();
});

it('rejects an invoice without line items before sending', function () {
    Http::fake();

    expect(fn () => SzamlaAgent::createInvoice(invoice(['items' => []])))
        ->toThrow(InvalidSzamlazzRequest::class, 'The invoice needs at least one line item.');

    Http::assertNothingSent();
});

it('keeps request validation errors catchable as InvalidArgumentException', function () {
    Http::fake();

    expect(fn () => SzamlaAgent::createInvoice(invoice(['items' => [
        ['name' => 'Tárhely', 'quantity' => 1, 'unit' => 'db', 'netUnitPrice' => 100, 'vatRate' => '27%'],
    ]])))->toThrow(InvalidArgumentException::class);
});

it('rejects a malformed VAT rate before sending', function () {
    Http::fake();

    expect(fn () => SzamlaAgent::createInvoice(invoice(['items' => [
        ['name' => 'Tárhely', 'quantity' => 1, 'unit' => 'db', 'netUnitPrice' => 100, 'vatRate' => '27%'],
    ]])))->toThrow(InvalidSzamlazzRequest::class, 'Invalid VAT rate "27%" for line item [Tárhely]');

    Http::assertNothingSent();
});

it('casts invoice input into typed values', function () {
    $invoice = invoice();

    expect($invoice->fulfillmentDate->format('Y-m-d'))->toBe('2026-11-10')
        ->and($invoice->language)->toBe(Language::Hungarian)
        ->and($invoice->buyer->taxPayerType)->toBe(TaxPayerType::HasHungarianTaxNumber)
        ->and($invoice->items)->toHaveCount(2)
        ->and($invoice->items[0]->quantity)->toBe(2.0);
});

it('marks proformas and other invoice types', function (InvoiceType $type, string $element) {
    Http::fake([AGENT_URL => Http::response(file_get_contents(__DIR__.'/../Fixtures/responses/invoice.xml'))]);

    SzamlaAgent::createInvoice(invoice(['type' => $type]));

    Http::assertSent(function (Request $request) use ($element): bool {
        $xml = sentXml($request, 'action-xmlagentxmlfile');
        expectValidAgainstXsd($xml, 'xmlszamla');

        return str_contains($xml, "<{$element}>true</{$element}>");
    });
})->with([
    [InvoiceType::Proforma, 'dijbekero'],
    [InvoiceType::Prepayment, 'elolegszamla'],
    [InvoiceType::Final, 'vegszamla'],
    [InvoiceType::Corrective, 'helyesbitoszamla'],
    [InvoiceType::DeliveryNote, 'szallitolevel'],
]);

it('throws a typed exception with the Számla Agent error code', function () {
    Http::fake([AGENT_URL => Http::response('xmlagenterror', 200, [
        'szlahu_error_code' => '57',
        'szlahu_error' => urlencode('Hiányzó vevő név'),
    ])]);

    SzamlaAgent::createInvoice(invoice());
})->throws(SzamlazzException::class, 'Számla Agent error [57]: Hiányzó vevő név');

it('exposes the error code on the exception', function () {
    Http::fake([AGENT_URL => Http::response(
        '<?xml version="1.0" encoding="UTF-8"?><xmlszamlavalasz xmlns="http://www.szamlazz.hu/xmlszamlavalasz"><sikeres>false</sikeres><hibakod>3</hibakod><hibauzenet>Sikertelen bejelentkezés.</hibauzenet></xmlszamlavalasz>',
    )]);

    try {
        SzamlaAgent::createInvoice(invoice());
        $this->fail('Expected exception.');
    } catch (SzamlazzException $exception) {
        expect($exception->errorCode)->toBe(3)
            ->and($exception->getMessage())->toContain('Sikertelen bejelentkezés.');
    }
});

it('wraps connection failures in the package exception', function () {
    Http::fake([AGENT_URL => Http::failedConnection('cURL error 28: Operation timed out')]);

    try {
        SzamlaAgent::createInvoice(invoice());
        $this->fail('Expected exception.');
    } catch (SzamlazzException $exception) {
        expect($exception->getMessage())->toContain('Could not reach the Számla Agent service')
            ->toContain('Operation timed out')
            ->and($exception->getPrevious())->toBeInstanceOf(ConnectionException::class)
            ->and($exception->errorCode)->toBeNull()
            ->and($exception->httpStatus)->toBeNull();
    }
});

it('reports maintenance', function () {
    Http::fake([AGENT_URL => Http::response('', 200, ['szlahu_down' => 'true'])]);

    SzamlaAgent::createInvoice(invoice());
})->throws(SzamlazzException::class, 'maintenance');

it('requires credentials', function () {
    config()->set('laravel-szamlazz.api_key', null);
    Http::fake();

    SzamlaAgent::createInvoice(invoice());
})->throws(SzamlazzException::class, 'credentials are missing');

it('can switch credentials per call', function () {
    Http::fake([AGENT_URL => Http::response(file_get_contents(__DIR__.'/../Fixtures/responses/invoice.xml'))]);

    SzamlaAgent::withCredentials(Credentials::fromLogin('demo', 'secret'))->createInvoice(invoice());

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-xmlagentxmlfile');
        expectValidAgainstXsd($xml, 'xmlszamla');

        return str_contains($xml, '<felhasznalo>demo</felhasznalo>')
            && str_contains($xml, '<jelszo>secret</jelszo>')
            && ! str_contains($xml, 'szamlaagentkulcs');
    });
});

it('reverses an invoice', function () {
    Http::fake([AGENT_URL => Http::response(file_get_contents(__DIR__.'/../Fixtures/responses/invoice.xml'))]);

    $result = SzamlaAgent::reverseInvoice(InvoiceReversal::from([
        'invoiceNumber' => 'E-TEST-2026-1',
        'issueDate' => '2026-11-12',
        'buyerEmail' => 'buyer@example.com',
    ]));

    expect($result->invoiceNumber)->toBe('E-TEST-2026-1');

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_st');
        expectValidAgainstXsd($xml, 'xmlszamlast');

        return str_contains($xml, '<szamlaszam>E-TEST-2026-1</szamlaszam>');
    });
});

it('registers payments on an invoice', function () {
    Http::fake([AGENT_URL => Http::response('', 200, ['szlahu_szamlaszam' => 'E-TEST-2026-1'])]);

    SzamlaAgent::registerPayment(PaymentRegistration::from([
        'invoiceNumber' => 'E-TEST-2026-1',
        'payments' => [
            ['date' => '2026-11-12', 'method' => 'átutalás', 'amount' => 25400],
        ],
    ]));

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_kifiz');
        expectValidAgainstXsd($xml, 'xmlszamlakifiz');

        return str_contains($xml, '<additiv>true</additiv>') && str_contains($xml, '<osszeg>25400</osszeg>');
    });
});

it('clears payments without sending an empty payment element', function () {
    Http::fake([AGENT_URL => Http::response('', 200, ['szlahu_szamlaszam' => 'E-TEST-2026-1'])]);

    SzamlaAgent::registerPayment(new PaymentRegistration('E-TEST-2026-1', payments: [], additive: false));

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_kifiz');
        expectValidAgainstXsd($xml, 'xmlszamlakifiz');

        return str_contains($xml, '<additiv>false</additiv>') && ! str_contains($xml, 'kifizetes');
    });
});

it('rejects more than five payments before sending', function () {
    Http::fake();

    $payment = ['date' => '2026-11-12', 'method' => 'átutalás', 'amount' => 100];

    expect(fn () => SzamlaAgent::registerPayment(PaymentRegistration::from([
        'invoiceNumber' => 'E-TEST-2026-1',
        'payments' => array_fill(0, 6, $payment),
    ])))->toThrow(InvalidSzamlazzRequest::class, 'at most 5 payments per registration, 6 given');

    Http::assertNothingSent();
});

it('accepts exactly five payments', function () {
    Http::fake([AGENT_URL => Http::response('', 200, ['szlahu_szamlaszam' => 'E-TEST-2026-1'])]);

    $payment = ['date' => '2026-11-12', 'method' => 'átutalás', 'amount' => 100];

    SzamlaAgent::registerPayment(PaymentRegistration::from([
        'invoiceNumber' => 'E-TEST-2026-1',
        'payments' => array_fill(0, 5, $payment),
    ]));

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_kifiz');
        expectValidAgainstXsd($xml, 'xmlszamlakifiz');

        return substr_count($xml, '<kifizetes>') === 5;
    });
});

it('downloads an invoice pdf', function () {
    Http::fake([AGENT_URL => Http::response('%PDF-1.4 test', 200, ['Content-Type' => 'application/pdf'])]);

    expect(SzamlaAgent::invoicePdf(InvoiceLookup::byInvoiceNumber('E-TEST-2026-1')))->toBe('%PDF-1.4 test');

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_pdf');
        expectValidAgainstXsd($xml, 'xmlszamlapdf');

        return str_contains($xml, '<szamlaszam>E-TEST-2026-1</szamlaszam>');
    });
});

it('downloads an invoice pdf by external id', function () {
    Http::fake([AGENT_URL => Http::response('%PDF-1.4 test', 200, ['Content-Type' => 'application/pdf'])]);

    expect(SzamlaAgent::invoicePdf(InvoiceLookup::byExternalId('ext-1')))->toBe('%PDF-1.4 test');

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_pdf');
        expectValidAgainstXsd($xml, 'xmlszamlapdf');

        return str_contains($xml, '<szamlaKulsoAzon>ext-1</szamlaKulsoAzon>');
    });
});

it('rejects a non pdf invoice pdf response', function () {
    Http::fake([AGENT_URL => Http::response('not a pdf')]);

    SzamlaAgent::invoicePdf(InvoiceLookup::byOrderNumber('ORDER-42'));
})->throws(SzamlazzException::class, 'not a PDF');

it('queries invoice data', function () {
    Http::fake([AGENT_URL => Http::response(file_get_contents(__DIR__.'/../Fixtures/responses/invoice-data.xml'))]);

    $details = SzamlaAgent::invoice(InvoiceLookup::byExternalId('ext-1'));

    expect($details)->toBeInstanceOf(InvoiceDetails::class)
        ->invoiceNumber->toBe('E-TEST-2026-1')
        ->id->toBe(123456)
        ->buyerName->toBe('Kovács Bt.')
        ->grossTotal->toBe(25400.0)
        ->and($details->dueDate?->format('Y-m-d'))->toBe('2026-11-19')
        ->and($details->hasPdf())->toBeFalse()
        ->and($details->xml)->toContain('<szamla');

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_xml');
        expectValidAgainstXsd($xml, 'xmlszamlaxml');

        return str_contains($xml, '<szamlaKulsoAzon>ext-1</szamlaKulsoAzon>');
    });
});

it('returns invoice data even when a response date is malformed', function () {
    $body = str_replace(
        ['<kelt>2026-11-11</kelt>', '<telj>2026-11-10</telj>', '<fizh>2026-11-19</fizh>'],
        ['<kelt>-</kelt>', '<telj>0000-00-00</telj>', '<fizh>2026-11-19 10:30:00</fizh>'],
        file_get_contents(__DIR__.'/../Fixtures/responses/invoice-data.xml'),
    );
    Http::fake([AGENT_URL => Http::response($body)]);

    $details = SzamlaAgent::invoice(InvoiceLookup::byInvoiceNumber('E-TEST-2026-1'));

    expect($details->invoiceNumber)->toBe('E-TEST-2026-1')
        ->and($details->issueDate)->toBeNull()
        ->and($details->fulfillmentDate)->toBeNull()
        ->and($details->dueDate?->toDateTimeString())->toBe('2026-11-19 00:00:00');
});

it('rejects an empty invoice lookup', function () {
    Http::fake();

    SzamlaAgent::invoicePdf(new InvoiceLookup);
})->throws(InvalidSzamlazzRequest::class);

it('deletes a proforma', function () {
    Http::fake([AGENT_URL => Http::response(
        '<?xml version="1.0" encoding="UTF-8"?><xmlszamladbkdelvalasz xmlns="http://www.szamlazz.hu/xmlszamladbkdelvalasz"><sikeres>true</sikeres></xmlszamladbkdelvalasz>',
    )]);

    SzamlaAgent::deleteProforma(InvoiceLookup::byOrderNumber('ORDER-42'));

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_dijbekero_torlese');
        expectValidAgainstXsd($xml, 'xmlszamladbkdel');

        return str_contains($xml, '<rendelesszam>ORDER-42</rendelesszam>');
    });
});

it('cannot delete a proforma by external id alone', function () {
    Http::fake();

    expect(fn () => SzamlaAgent::deleteProforma(InvoiceLookup::byExternalId('ext-1')))
        ->toThrow(InvalidSzamlazzRequest::class, 'an external id alone is not supported');

    Http::assertNothingSent();
});

it('deletes a proforma by invoice number when the lookup also has an external id', function () {
    Http::fake([AGENT_URL => Http::response(
        '<?xml version="1.0" encoding="UTF-8"?><xmlszamladbkdelvalasz xmlns="http://www.szamlazz.hu/xmlszamladbkdelvalasz"><sikeres>true</sikeres></xmlszamladbkdelvalasz>',
    )]);

    SzamlaAgent::deleteProforma(new InvoiceLookup(invoiceNumber: 'D-TEST-2026-1', externalId: 'ext-1'));

    Http::assertSent(function (Request $request): bool {
        $xml = sentXml($request, 'action-szamla_agent_dijbekero_torlese');
        expectValidAgainstXsd($xml, 'xmlszamladbkdel');

        return str_contains($xml, '<szamlaszam>D-TEST-2026-1</szamlaszam>') && ! str_contains($xml, 'ext-1');
    });
});

it('accepts date objects as invoice dates', function () {
    $invoice = invoice(['fulfillmentDate' => now()->setDate(2026, 11, 1), 'dueDate' => new DateTimeImmutable('2026-11-30')]);

    expect($invoice->fulfillmentDate->format('Y-m-d'))->toBe('2026-11-01')
        ->and($invoice->dueDate->format('Y-m-d'))->toBe('2026-11-30');
});

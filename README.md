<div align="center">
    <h1>Laravel Szamlazz</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/szabrob/laravel-szamlazz"><img src="https://img.shields.io/packagist/v/szabrob/laravel-szamlazz.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/szabrob/laravel-szamlazz"><img src="https://img.shields.io/packagist/php-v/szabrob/laravel-szamlazz.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/szabrob/laravel-szamlazz"><img src="https://badge.laravel.cloud/badge/szabrob/laravel-szamlazz?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/szabrob/laravel-szamlazz/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/szabrob/laravel-szamlazz/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/szabrob/laravel-szamlazz"><img src="https://img.shields.io/packagist/dt/szabrob/laravel-szamlazz.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Laravel integration for the Számlázz.hu Számla Agent API.

## Installation

You can install the package via Composer:

```bash
composer require szabrob/laravel-szamlazz
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="laravel-szamlazz"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="laravel-szamlazz-config"
```

## Configuration

Set your Számla Agent key (Számlázz.hu → Beállítások → Számla Agent kulcsok) in `.env`:

```dotenv
SZAMLAZZ_API_KEY=your-agent-key

# or a username / password pair instead of the key
# SZAMLAZZ_USERNAME=
# SZAMLAZZ_PASSWORD=

# optional
# SZAMLAZZ_E_INVOICE=true
# SZAMLAZZ_DOWNLOAD_PDF=true
# SZAMLAZZ_TIMEOUT=30
```

## Usage

Every request and response is a typed [`spatie/laravel-data`](https://spatie.be/docs/laravel-data) object. Build requests with the constructor or with `::from([...])`, then send them through the `SzamlaAgent` facade (or inject `Szabrob\LaravelSzamlazz\SzamlaAgent`).

### Create an invoice

```php
use Szabrob\LaravelSzamlazz\Data\Invoice\Invoice;
use Szabrob\LaravelSzamlazz\Facades\SzamlaAgent;

$result = SzamlaAgent::createInvoice(Invoice::from([
    'fulfillmentDate' => '2026-11-10',
    'dueDate' => '2026-11-18',
    'paymentMethod' => 'Átutalás',
    'currency' => 'HUF',
    'orderNumber' => 'ORDER-42',
    'buyer' => [
        'name' => 'Kovács Bt.',
        'postCode' => '2030',
        'city' => 'Érd',
        'address' => 'Tárnoki út 23.',
        'email' => 'buyer@example.com',
        'sendEmail' => true,
        'taxNumber' => '12345678-1-42',
    ],
    'items' => [
        ['name' => 'Webfejlesztés', 'quantity' => 2, 'unit' => 'óra', 'netUnitPrice' => 10000, 'vatRate' => '27'],
    ],
]));

$result->invoiceNumber; // "E-ABC-2026-1"
$result->grossTotal;    // 25400.0

if ($result->hasPdf()) {
    Storage::put("invoices/{$result->invoiceNumber}.pdf", $result->pdf);
}
```

Line item `netAmount`, `vatAmount` and `grossAmount` are calculated from `quantity × netUnitPrice` and a numeric `vatRate` when omitted. Non-numeric VAT keys such as `AAM`, `TAM` or `EU` calculate zero VAT.

Set `type` to an `InvoiceType` to create other documents:

```php
use Szabrob\LaravelSzamlazz\Enums\InvoiceType;

Invoice::from([...$data, 'type' => InvoiceType::Proforma]);     // díjbekérő
Invoice::from([...$data, 'type' => InvoiceType::Prepayment]);   // előlegszámla
Invoice::from([...$data, 'type' => InvoiceType::Final]);        // végszámla
Invoice::from([...$data, 'type' => InvoiceType::Corrective, 'correctedInvoiceNumber' => 'E-ABC-2026-1']);
Invoice::from([...$data, 'type' => InvoiceType::DeliveryNote]); // szállítólevél
```

### Other invoice operations

```php
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceLookup;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceReversal;
use Szabrob\LaravelSzamlazz\Data\Invoice\PaymentRegistration;

// Storno
$reversal = SzamlaAgent::reverseInvoice(new InvoiceReversal(invoiceNumber: 'E-ABC-2026-1'));

// Register a payment (jóváírás)
SzamlaAgent::registerPayment(PaymentRegistration::from([
    'invoiceNumber' => 'E-ABC-2026-1',
    'payments' => [
        ['date' => '2026-11-12', 'method' => 'átutalás', 'amount' => 25400],
    ],
]));

// Download the PDF (raw bytes)
$pdf = SzamlaAgent::invoicePdf(InvoiceLookup::byInvoiceNumber('E-ABC-2026-1'));

// Query invoice data (InvoiceDetails, the raw XML is available in ->xml)
$details = SzamlaAgent::invoice(InvoiceLookup::byOrderNumber('ORDER-42'), withPdf: false);

// Delete a proforma
SzamlaAgent::deleteProforma(InvoiceLookup::byInvoiceNumber('D-ABC-2026-1'));
```

### Receipts (nyugta)

```php
use Szabrob\LaravelSzamlazz\Data\Receipt\Receipt;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptEmail;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptLookup;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptReversal;

$receipt = SzamlaAgent::createReceipt(Receipt::from([
    'prefix' => 'NYGT',
    'callId' => 'order-42', // idempotency key
    'paymentMethod' => 'készpénz',
    'items' => [
        ['name' => 'Kávé', 'quantity' => 2, 'unit' => 'db', 'netUnitPrice' => 500, 'vatRate' => '27'],
    ],
]));

SzamlaAgent::receipt(ReceiptLookup::byReceiptNumber($receipt->receiptNumber));
SzamlaAgent::sendReceipt(new ReceiptEmail($receipt->receiptNumber, 'buyer@example.com'));
SzamlaAgent::reverseReceipt(new ReceiptReversal($receipt->receiptNumber));
```

### Multiple accounts

```php
use Szabrob\LaravelSzamlazz\Data\Credentials;

SzamlaAgent::withCredentials(Credentials::fromApiKey($tenant->szamlazz_key))
    ->createInvoice($invoice);
```

### Error handling

Számla Agent errors throw `Szabrob\LaravelSzamlazz\Exceptions\SzamlazzException`. The Számla Agent error code is available in `$exception->errorCode`, the HTTP status in `$exception->httpStatus`.

```php
use Szabrob\LaravelSzamlazz\Exceptions\SzamlazzException;

try {
    SzamlaAgent::createInvoice($invoice);
} catch (SzamlazzException $exception) {
    report($exception);
}
```

### Testing

The package uses the Laravel HTTP client, so `Http::fake()` works in your application tests:

```php
Http::fake([
    'www.szamlazz.hu/*' => Http::response('<?xml version="1.0" encoding="UTF-8"?>
        <xmlszamlavalasz xmlns="http://www.szamlazz.hu/xmlszamlavalasz">
            <sikeres>true</sikeres>
            <szamlaszam>E-TEST-2026-1</szamlaszam>
        </xmlszamlavalasz>'),
]);
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Szamlazz! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Szabó Róbert](https://github.com/szabrob)
- [All Contributors](../../contributors)

## License

Laravel Szamlazz is open-sourced software licensed under the [MIT license](LICENSE.md).

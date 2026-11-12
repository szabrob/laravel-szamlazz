---
name: laravel-szamlazz-development
description: >
  Issue Számlázz.hu invoices, proformas and receipts from Laravel applications with the
  szabrob/laravel-szamlazz Számla Agent client and its typed spatie/laravel-data DTOs.
license: MIT
metadata:
  author: Szabó Róbert
---

# Laravel Szamlazz

Use this skill when a Laravel application needs to create or manage Számlázz.hu documents through the Számla Agent API.

## Primary Goal

- send typed DTOs through `Szabrob\LaravelSzamlazz\Facades\SzamlaAgent` (or inject `Szabrob\LaravelSzamlazz\SzamlaAgent`) and store the typed results

## Workflow

### 1. Install and configure

- `composer require szabrob/laravel-szamlazz`
- set `SZAMLAZZ_API_KEY` in `.env` (or `SZAMLAZZ_USERNAME` + `SZAMLAZZ_PASSWORD`)
- optional: `SZAMLAZZ_E_INVOICE`, `SZAMLAZZ_DOWNLOAD_PDF`, `SZAMLAZZ_TIMEOUT`, `SZAMLAZZ_AGGREGATOR`
- publish config only when needed: `php artisan vendor:publish --tag="laravel-szamlazz-config"`

### 2. Build requests with DTOs

- invoices: `Data\Invoice\Invoice` with `Buyer`, optional `Seller`, `InvoiceItem[]`; `type` is an `Enums\InvoiceType` (Invoice, Proforma, Prepayment, Final, Corrective, DeliveryNote)
- dates accept `Y-m-d` strings or `DateTimeInterface`; enums accept their backing values in `::from([...])`
- item `netAmount`/`vatAmount`/`grossAmount` are filled in when omitted so net + VAT = gross (gross-only prices work); three given amounts that do not add up throw `InvalidArgumentException`; `vatRate` is a string: a dot-decimal percentage ("27", "5", "0", "5.5") or a Számla Agent VAT key ("AAM", "TAM", "EU", "F.AFA", ...); anything else (e.g. "27%", "27,0") throws `InvalidArgumentException`
- receipts: `Data\Receipt\Receipt` with `ReceiptItem[]` and optional `ReceiptPayment[]`; use `callId` as an idempotency key

### 3. Call the client

- `createInvoice(Invoice): InvoiceResult`, `reverseInvoice(InvoiceReversal): InvoiceResult`
- `registerPayment(PaymentRegistration): void` (max 5 payments per call, more throws `InvalidArgumentException` before sending)
- `invoicePdf(InvoiceLookup): string` (raw PDF bytes), `invoice(InvoiceLookup, bool $withPdf = false): InvoiceDetails`
- `deleteProforma(InvoiceLookup): void` (needs an invoice number or order number; an external id on the lookup is ignored)
- `createReceipt(Receipt)`, `reverseReceipt(ReceiptReversal)`, `receipt(ReceiptLookup)` return `ReceiptResult`; `sendReceipt(ReceiptEmail): void`
- per-tenant keys: `SzamlaAgent::withCredentials(Credentials::fromApiKey($key))->createInvoice(...)`; runtime `config(['laravel-szamlazz.api_key' => ...])` changes also apply to the next facade call, but not to a client instance already injected into a long-lived object

### 4. Handle results and errors

- results expose `invoiceNumber` / `receiptNumber`, totals and `pdf` (raw bytes, hidden from `toArray()`); check `hasPdf()` before storing
- failures, including timeouts and connection errors, throw `Exceptions\SzamlazzException` with `errorCode` (Számla Agent code) and `httpStatus`
- after a timeout, look the document up by order number or call id before retrying; it may already exist

## Examples

```php
use Szabrob\LaravelSzamlazz\Data\Invoice\Invoice;
use Szabrob\LaravelSzamlazz\Facades\SzamlaAgent;

$result = SzamlaAgent::createInvoice(Invoice::from([
    'fulfillmentDate' => $order->paid_at,
    'dueDate' => $order->paid_at,
    'paymentMethod' => 'Bankkártya',
    'orderNumber' => $order->number,
    'paid' => true,
    'buyer' => ['name' => $order->name, 'postCode' => $order->zip, 'city' => $order->city, 'address' => $order->street, 'email' => $order->email, 'sendEmail' => true],
    'items' => $order->lines->map(fn ($line) => [
        'name' => $line->name, 'quantity' => $line->qty, 'unit' => 'db', 'netUnitPrice' => $line->net_price, 'vatRate' => '27',
    ])->all(),
]));

$order->update(['invoice_number' => $result->invoiceNumber]);
```

Testing in the app: fake `www.szamlazz.hu/*` with `Http::fake()` and return an `xmlszamlavalasz` XML body with `<sikeres>true</sikeres>` and `<szamlaszam>`.

## Anti-patterns

- do not build Számla Agent XML by hand or post to szamlazz.hu directly; use the DTOs and client
- do not call `env()` for the key in app code; use the package config
- do not use classes under `Szabrob\LaravelSzamlazz\Support` or `Enums\Action`; they are internal
- do not create invoices inside a DB transaction that may roll back; store the returned invoice number after success

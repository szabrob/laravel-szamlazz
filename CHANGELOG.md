# Release Notes

## [Unreleased](https://github.com/szabrob/laravel-szamlazz/compare/v1.0.0...HEAD)

## [v1.0.0](https://github.com/szabrob/laravel-szamlazz/releases/tag/v1.0.0) - 2026-11-12

First stable release. Requires PHP 8.3+ and Laravel 12 or 13.

### Invoices

- Create invoices, proformas (díjbekérő), prepayment, final and corrective invoices, and delivery notes through `InvoiceType`.
- Reverse (storno) an invoice, register payments (at most 5 per call), download an invoice PDF, query invoice data (with the raw XML and an optional PDF), and delete proformas.
- Look up invoices by invoice number, order number or external id with `InvoiceLookup`.

### Receipts

- Create, reverse, query and email receipts (nyugta). `callId` works as an idempotency key.

### Client

- Typed `spatie/laravel-data` requests and results. Request DTOs accept `Y-m-d` strings or `DateTimeInterface` dates and enum backing values via `::from([...])`.
- Line item net, VAT and gross amounts are filled in when omitted, so net + VAT always equals gross. VAT rates are dot-decimal percentages or Számla Agent VAT keys.
- `SzamlaAgent` facade and injectable `Szabrob\LaravelSzamlazz\SzamlaAgent` client. Runtime config changes apply to the next call, and `withCredentials()` switches accounts per call.
- Agent key or username and password authentication, configurable base URL, timeout, aggregator, and e-invoice and PDF download defaults. Invoices and invoice reversals can override both defaults per request, receipts the PDF download default.
- Result DTOs expose the PDF bytes (hidden from `toArray()` and JSON output) with `hasPdf()`.

### Errors

- Számla Agent errors, maintenance and connection failures throw `SzamlazzException` with `errorCode` and `httpStatus`.
- Invalid requests (malformed VAT rate, inconsistent amounts, no line items, more than 5 payments, empty lookups) throw `InvalidSzamlazzRequest` before anything is sent. It extends `InvalidArgumentException`.

### Tooling

- Publishable config (`laravel-szamlazz-config` tag).
- Bundled Laravel Boost skill.

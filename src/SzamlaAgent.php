<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Szabrob\LaravelSzamlazz\Data\Credentials;
use Szabrob\LaravelSzamlazz\Data\Invoice\Invoice;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceLookup;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceReversal;
use Szabrob\LaravelSzamlazz\Data\Invoice\PaymentRegistration;
use Szabrob\LaravelSzamlazz\Data\Receipt\Receipt;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptEmail;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptLookup;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptReversal;
use Szabrob\LaravelSzamlazz\Data\Results\InvoiceDetails;
use Szabrob\LaravelSzamlazz\Data\Results\InvoiceResult;
use Szabrob\LaravelSzamlazz\Data\Results\ReceiptResult;
use Szabrob\LaravelSzamlazz\Enums\Action;
use Szabrob\LaravelSzamlazz\Exceptions\SzamlazzException;
use Szabrob\LaravelSzamlazz\Support\Payloads;
use Szabrob\LaravelSzamlazz\Support\XmlReader;

class SzamlaAgent
{
    public const string DEFAULT_BASE_URL = 'https://www.szamlazz.hu/szamla/';

    public function __construct(
        protected HttpFactory $http,
        protected Credentials $credentials,
        protected string $baseUrl = self::DEFAULT_BASE_URL,
        protected int $timeout = 30,
        protected bool $eInvoice = true,
        protected bool $downloadPdf = true,
        protected ?string $aggregator = null,
    ) {}

    /**
     * Use different credentials, e.g. per tenant, without changing the configuration.
     */
    public function withCredentials(Credentials $credentials): static
    {
        $clone = clone $this;
        $clone->credentials = $credentials;

        return $clone;
    }

    public function createInvoice(Invoice $invoice): InvoiceResult
    {
        $response = $this->send(Action::CreateInvoice, $this->payloads()->invoice($invoice));

        return $this->invoiceResult($response);
    }

    public function reverseInvoice(InvoiceReversal $reversal): InvoiceResult
    {
        $response = $this->send(Action::ReverseInvoice, $this->payloads()->invoiceReversal($reversal));

        return $this->invoiceResult($response);
    }

    public function registerPayment(PaymentRegistration $registration): void
    {
        $this->send(Action::RegisterPayment, $this->payloads()->paymentRegistration($registration));
    }

    /**
     * Download the PDF of an existing invoice and return the raw PDF bytes.
     */
    public function invoicePdf(InvoiceLookup $lookup): string
    {
        $response = $this->send(Action::InvoicePdf, $this->payloads()->invoicePdf($lookup));
        $body = $response->body();

        if (! str_starts_with($body, '%PDF')) {
            throw SzamlazzException::unexpectedResponse('the invoice PDF response is not a PDF document.', $response->status());
        }

        return $body;
    }

    public function invoice(InvoiceLookup $lookup, bool $withPdf = false): InvoiceDetails
    {
        $response = $this->send(Action::InvoiceData, $this->payloads()->invoiceData($lookup, $withPdf));
        $xml = $this->xml($response);

        return new InvoiceDetails(
            invoiceNumber: $xml->string('alap/szamlaszam') ?? throw SzamlazzException::unexpectedResponse('missing invoice number.', $response->status()),
            id: $xml->int('alap/id'),
            type: $xml->string('alap/tipus'),
            issueDate: $this->date($xml->string('alap/kelt')),
            fulfillmentDate: $this->date($xml->string('alap/telj')),
            dueDate: $this->date($xml->string('alap/fizh')),
            paymentMethod: $xml->string('alap/fizmod'),
            currency: $xml->string('alap/devizanem'),
            buyerName: $xml->string('vevo/nev'),
            buyerTaxNumber: $xml->string('vevo/adoszam'),
            buyerEmail: $xml->string('vevo/email'),
            netTotal: $xml->float('osszegek/totalossz/netto'),
            vatTotal: $xml->float('osszegek/totalossz/afa'),
            grossTotal: $xml->float('osszegek/totalossz/brutto'),
            pdf: $this->pdf($xml->string('pdf')),
            xml: $xml->xml,
        );
    }

    public function deleteProforma(InvoiceLookup $lookup): void
    {
        $this->send(Action::DeleteProforma, $this->payloads()->deleteProforma($lookup));
    }

    public function createReceipt(Receipt $receipt): ReceiptResult
    {
        return $this->receiptResult($this->send(Action::CreateReceipt, $this->payloads()->receipt($receipt)));
    }

    public function reverseReceipt(ReceiptReversal $reversal): ReceiptResult
    {
        return $this->receiptResult($this->send(Action::ReverseReceipt, $this->payloads()->receiptReversal($reversal)));
    }

    public function receipt(ReceiptLookup $lookup): ReceiptResult
    {
        return $this->receiptResult($this->send(Action::GetReceipt, $this->payloads()->receiptLookup($lookup)));
    }

    public function sendReceipt(ReceiptEmail $email): void
    {
        $this->send(Action::SendReceipt, $this->payloads()->receiptEmail($email));
    }

    protected function payloads(): Payloads
    {
        if (! $this->credentials->isComplete()) {
            throw SzamlazzException::missingCredentials();
        }

        return new Payloads($this->credentials, $this->eInvoice, $this->downloadPdf, $this->aggregator);
    }

    protected function send(Action $action, string $xml): Response
    {
        try {
            $response = $this->http
                ->timeout($this->timeout)
                ->attach($action->field(), $xml, 'request.xml', ['Content-Type' => 'text/xml'])
                ->post($this->baseUrl);
        } catch (ConnectionException $exception) {
            throw SzamlazzException::connectionFailed($exception);
        }

        $this->guard($response);

        return $response;
    }

    protected function guard(Response $response): void
    {
        if ($response->header('szlahu_down') !== '') {
            throw SzamlazzException::maintenance();
        }

        $errorCode = $response->header('szlahu_error_code');
        $error = $response->header('szlahu_error');

        if ($errorCode !== '' || $error !== '') {
            throw SzamlazzException::fromApi(
                is_numeric($errorCode) ? (int) $errorCode : null,
                urldecode($error),
                $response->status(),
            );
        }

        $xml = XmlReader::tryParse($response->body());

        if ($xml?->bool('sikeres') === false) {
            throw SzamlazzException::fromApi($xml->int('hibakod'), $xml->string('hibauzenet'), $response->status());
        }

        if ($response->failed()) {
            throw SzamlazzException::unexpectedResponse("HTTP {$response->status()}.", $response->status());
        }
    }

    protected function invoiceResult(Response $response): InvoiceResult
    {
        $xml = $this->xml($response);
        $header = static fn (string $name): ?string => $response->header($name) !== '' ? urldecode($response->header($name)) : null;
        $headerFloat = static fn (string $name): ?float => is_numeric($header($name)) ? (float) $header($name) : null;

        return new InvoiceResult(
            invoiceNumber: $xml->string('szamlaszam') ?? $header('szlahu_szamlaszam')
                ?? throw SzamlazzException::unexpectedResponse('missing invoice number.', $response->status()),
            id: is_numeric($header('szlahu_id')) ? (int) $header('szlahu_id') : null,
            netTotal: $xml->float('szamlanetto') ?? $headerFloat('szlahu_nettovegosszeg'),
            grossTotal: $xml->float('szamlabrutto') ?? $headerFloat('szlahu_bruttovegosszeg'),
            outstandingAmount: $xml->float('kintlevoseg') ?? $headerFloat('szlahu_kintlevoseg'),
            customerAccountUrl: $xml->string('vevoifiokurl') ?? $header('szlahu_vevoifiokurl'),
            pdf: $this->pdf($xml->string('pdf')),
        );
    }

    protected function receiptResult(Response $response): ReceiptResult
    {
        $xml = $this->xml($response);

        return new ReceiptResult(
            receiptNumber: $xml->string('nyugta/alap/nyugtaszam')
                ?? throw SzamlazzException::unexpectedResponse('missing receipt number.', $response->status()),
            id: $xml->int('nyugta/alap/id'),
            callId: $xml->string('nyugta/alap/hivasAzonosito'),
            type: $xml->string('nyugta/alap/tipus'),
            reversed: $xml->bool('nyugta/alap/stornozott') ?? false,
            reversedReceiptNumber: $xml->string('nyugta/alap/stornozottNyugtaszam'),
            issueDate: $this->date($xml->string('nyugta/alap/kelt')),
            paymentMethod: $xml->string('nyugta/alap/fizmod'),
            currency: $xml->string('nyugta/alap/penznem'),
            netTotal: $xml->float('nyugta/osszegek/totalossz/netto'),
            vatTotal: $xml->float('nyugta/osszegek/totalossz/afa'),
            grossTotal: $xml->float('nyugta/osszegek/totalossz/brutto'),
            pdf: $this->pdf($xml->string('nyugtaPdf')),
        );
    }

    protected function xml(Response $response): XmlReader
    {
        return XmlReader::tryParse($response->body())
            ?? throw SzamlazzException::unexpectedResponse('the response body is not XML.', $response->status());
    }

    protected function pdf(?string $base64): ?string
    {
        if ($base64 === null) {
            return null;
        }

        $decoded = base64_decode($base64, true);

        return $decoded === false ? null : $decoded;
    }

    protected function date(?string $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value);
    }
}

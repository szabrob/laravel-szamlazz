<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use Carbon\CarbonImmutable;
use DOMDocument;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Szabrob\LaravelSzamlazz\Data\Credentials;
use Szabrob\LaravelSzamlazz\Data\Invoice\Buyer;
use Szabrob\LaravelSzamlazz\Data\Invoice\Invoice;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceItem;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceLookup;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoicePayment;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceReversal;
use Szabrob\LaravelSzamlazz\Data\Invoice\PaymentRegistration;
use Szabrob\LaravelSzamlazz\Data\Invoice\Seller;
use Szabrob\LaravelSzamlazz\Data\Receipt\Receipt;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptEmail;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptItem;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptLookup;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptPayment;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptReversal;
use Szabrob\LaravelSzamlazz\Data\Results\InvoiceDetails;
use Szabrob\LaravelSzamlazz\Data\Results\InvoiceResult;
use Szabrob\LaravelSzamlazz\Data\Results\ReceiptResult;
use Szabrob\LaravelSzamlazz\Enums\InvoiceTemplate;
use Szabrob\LaravelSzamlazz\Enums\InvoiceType;
use Szabrob\LaravelSzamlazz\Enums\Language;
use Szabrob\LaravelSzamlazz\Enums\TaxPayerType;
use Szabrob\LaravelSzamlazz\Exceptions\SzamlazzException;
use Szabrob\LaravelSzamlazz\SzamlaAgent;
use Throwable;
use Workbench\App\Support\ConsoleForms;

/**
 * Workbench test console: calls every SzamlaAgent method from a browser form and
 * shows the result DTO, the sent XML request and the raw Számla Agent response.
 */
final class SzamlazzConsoleController
{
    private const string PDF_DIRECTORY = 'app/szamlazz-workbench';

    private const string SESSION_API_KEY = 'szamlazz-workbench.api_key';

    public function show(Request $request, ?string $operation = null): View|RedirectResponse
    {
        $forms = ConsoleForms::all();
        $operation ??= array_key_first($forms);

        if (! isset($forms[$operation])) {
            return redirect()->route('szamlazz.console');
        }

        return $this->view($request, $forms, $operation);
    }

    public function run(Request $request, string $operation): View|RedirectResponse
    {
        $forms = ConsoleForms::all();

        if (! isset($forms[$operation])) {
            return redirect()->route('szamlazz.console');
        }

        $exchanges = [];
        Event::listen(ResponseReceived::class, function (ResponseReceived $event) use (&$exchanges): void {
            $exchanges[] = [
                'url' => $event->request->url(),
                'request' => $this->requestXml($event->request->body()),
                'status' => $event->response->status(),
                'headers' => collect($event->response->headers())
                    ->filter(static fn (array $values, string $name): bool => str_starts_with(strtolower($name), 'szlahu'))
                    ->map(static fn (array $values): string => urldecode(implode(', ', $values)))
                    ->all(),
                'response' => $this->responseBody($event->response->body()),
            ];
        });

        $startedAt = microtime(true);

        try {
            $outcome = $this->call($this->agent($request), $operation, $request);
            $outcome['ok'] = true;
        } catch (SzamlazzException $exception) {
            $outcome = [
                'ok' => false,
                'error' => $exception->getMessage(),
                'details' => array_filter([
                    'Hibakód' => $exception->errorCode,
                    'HTTP státusz' => $exception->httpStatus,
                ], static fn (mixed $value): bool => $value !== null),
            ];
        } catch (Throwable $exception) {
            $outcome = [
                'ok' => false,
                'error' => $exception::class.': '.$exception->getMessage(),
                'details' => [],
            ];
        }

        $outcome['duration'] = (int) round((microtime(true) - $startedAt) * 1000);
        $outcome['exchanges'] = $exchanges;

        return $this->view($request, $forms, $operation, $outcome);
    }

    public function credentials(Request $request): RedirectResponse
    {
        $apiKey = trim((string) $request->input('api_key'));

        if ($request->boolean('clear') || $apiKey === '') {
            $request->session()->forget(self::SESSION_API_KEY);
        } else {
            $request->session()->put(self::SESSION_API_KEY, $apiKey);
        }

        return redirect()->back();
    }

    public function pdf(string $file): BinaryFileResponse
    {
        abort_unless(preg_match('/^[a-z0-9-]+\.pdf$/', $file) === 1, 404);

        $path = storage_path(self::PDF_DIRECTORY.'/'.$file);

        abort_unless(is_file($path), 404);

        return response()->file($path, ['Content-Type' => 'application/pdf']);
    }

    /**
     * @param  array<string, array<string, mixed>>  $forms
     * @param  array<string, mixed>|null  $outcome
     */
    private function view(Request $request, array $forms, string $operation, ?array $outcome = null): View
    {
        $sessionKey = $request->session()->get(self::SESSION_API_KEY);
        $configKey = config('laravel-szamlazz.api_key');
        $configUser = config('laravel-szamlazz.username');

        return view('szamlazz-console', [
            'forms' => $forms,
            'operation' => $operation,
            'form' => $forms[$operation],
            'submitted' => $outcome !== null,
            'outcome' => $outcome,
            'credentials' => match (true) {
                filled($sessionKey) => 'Munkamenet Agent kulcs: '.$this->mask((string) $sessionKey),
                filled($configKey) => 'Config Agent kulcs: '.$this->mask((string) $configKey),
                filled($configUser) => 'Config felhasználó: '.$configUser,
                default => null,
            },
            'hasSessionKey' => filled($sessionKey),
            'baseUrl' => config('laravel-szamlazz.base_url') ?: SzamlaAgent::DEFAULT_BASE_URL,
        ]);
    }

    private function agent(Request $request): SzamlaAgent
    {
        $agent = app(SzamlaAgent::class);
        $apiKey = $request->session()->get(self::SESSION_API_KEY);

        return filled($apiKey) ? $agent->withCredentials(Credentials::fromApiKey((string) $apiKey)) : $agent;
    }

    /**
     * @return array<string, mixed>
     */
    private function call(SzamlaAgent $agent, string $operation, Request $request): array
    {
        return match ($operation) {
            'create-invoice' => $this->result($agent->createInvoice($this->invoice($request))),
            'reverse-invoice' => $this->result($agent->reverseInvoice(new InvoiceReversal(
                invoiceNumber: $this->required($request, 'invoiceNumber'),
                issueDate: $this->date($request, 'issueDate'),
                fulfillmentDate: $this->date($request, 'fulfillmentDate'),
                comment: $this->string($request, 'comment'),
                buyerEmail: $this->string($request, 'buyerEmail'),
                externalId: $this->string($request, 'externalId'),
                eInvoice: $this->tristate($request, 'eInvoice'),
                downloadPdf: $this->tristate($request, 'downloadPdf'),
            ))),
            'register-payment' => $this->registerPayment($agent, $request),
            'invoice-pdf' => $this->invoicePdf($agent, $request),
            'invoice-data' => $this->result($agent->invoice($this->invoiceLookup($request), $request->boolean('withPdf')), withXml: true),
            'delete-proforma' => $this->done($agent->deleteProforma(new InvoiceLookup(
                invoiceNumber: $this->string($request, 'invoiceNumber'),
                orderNumber: $this->string($request, 'orderNumber'),
            )), 'A díjbekérő törölve.'),
            'create-receipt' => $this->result($agent->createReceipt($this->receipt($request))),
            'reverse-receipt' => $this->result($agent->reverseReceipt(new ReceiptReversal(
                receiptNumber: $this->required($request, 'receiptNumber'),
                callId: $this->string($request, 'callId'),
                pdfTemplate: $this->string($request, 'pdfTemplate'),
                downloadPdf: $this->tristate($request, 'downloadPdf'),
            ))),
            'get-receipt' => $this->result($agent->receipt(new ReceiptLookup(
                receiptNumber: $this->string($request, 'receiptNumber'),
                orderNumber: $this->string($request, 'orderNumber'),
                callId: $this->string($request, 'callId'),
                pdfTemplate: $this->string($request, 'pdfTemplate'),
                downloadPdf: $this->tristate($request, 'downloadPdf'),
            ))),
            'send-receipt' => $this->done($agent->sendReceipt(new ReceiptEmail(
                receiptNumber: $this->required($request, 'receiptNumber'),
                email: $this->required($request, 'email'),
                replyTo: $this->string($request, 'replyTo'),
                subject: $this->string($request, 'subject'),
                body: $this->string($request, 'body'),
            )), 'A nyugta elküldve.'),
        };
    }

    private function invoice(Request $request): Invoice
    {
        $taxPayerType = $this->string($request, 'buyer.taxPayerType');
        $template = $this->string($request, 'template');

        return new Invoice(
            buyer: new Buyer(
                name: $this->required($request, 'buyer.name'),
                postCode: $this->required($request, 'buyer.postCode'),
                city: $this->required($request, 'buyer.city'),
                address: $this->required($request, 'buyer.address'),
                country: $this->string($request, 'buyer.country'),
                email: $this->string($request, 'buyer.email'),
                sendEmail: $request->boolean('buyer.sendEmail'),
                taxPayerType: $taxPayerType !== null ? TaxPayerType::from((int) $taxPayerType) : null,
                taxNumber: $this->string($request, 'buyer.taxNumber'),
                euTaxNumber: $this->string($request, 'buyer.euTaxNumber'),
                phone: $this->string($request, 'buyer.phone'),
            ),
            items: array_map(fn (array $row): InvoiceItem => new InvoiceItem(
                name: (string) $row['name'],
                quantity: (float) ($row['quantity'] ?? 0),
                unit: (string) ($row['unit'] ?? ''),
                netUnitPrice: (float) ($row['netUnitPrice'] ?? 0),
                vatRate: (string) ($row['vatRate'] ?? ''),
                comment: $this->filled($row['comment'] ?? null),
            ), $this->rows($request, 'items', 'name')),
            fulfillmentDate: $this->date($request, 'fulfillmentDate') ?? CarbonImmutable::today(),
            dueDate: $this->date($request, 'dueDate') ?? CarbonImmutable::today(),
            paymentMethod: $this->string($request, 'paymentMethod') ?? 'Átutalás',
            currency: $this->string($request, 'currency') ?? 'HUF',
            language: Language::from($this->string($request, 'language') ?? Language::Hungarian->value),
            type: InvoiceType::from($this->string($request, 'type') ?? InvoiceType::Invoice->value),
            issueDate: $this->date($request, 'issueDate'),
            seller: $this->seller($request),
            comment: $this->string($request, 'comment'),
            orderNumber: $this->string($request, 'orderNumber'),
            proformaNumber: $this->string($request, 'proformaNumber'),
            prepaymentInvoiceNumber: $this->string($request, 'prepaymentInvoiceNumber'),
            correctedInvoiceNumber: $this->string($request, 'correctedInvoiceNumber'),
            prefix: $this->string($request, 'prefix'),
            paid: $request->boolean('paid'),
            template: $template !== null ? InvoiceTemplate::from($template) : null,
            preview: $request->boolean('preview'),
            externalId: $this->string($request, 'externalId'),
            eInvoice: $this->tristate($request, 'eInvoice'),
            downloadPdf: $this->tristate($request, 'downloadPdf'),
        );
    }

    private function seller(Request $request): ?Seller
    {
        $seller = new Seller(
            bank: $this->string($request, 'seller.bank'),
            bankAccount: $this->string($request, 'seller.bankAccount'),
            emailReplyTo: $this->string($request, 'seller.emailReplyTo'),
            emailSubject: $this->string($request, 'seller.emailSubject'),
            emailBody: $this->string($request, 'seller.emailBody'),
        );

        return array_filter($seller->toArray(), static fn (mixed $value): bool => $value !== null) === [] ? null : $seller;
    }

    private function receipt(Request $request): Receipt
    {
        return new Receipt(
            prefix: $this->required($request, 'prefix'),
            items: array_map(fn (array $row): ReceiptItem => new ReceiptItem(
                name: (string) $row['name'],
                quantity: (float) ($row['quantity'] ?? 0),
                unit: (string) ($row['unit'] ?? ''),
                netUnitPrice: (float) ($row['netUnitPrice'] ?? 0),
                vatRate: (string) ($row['vatRate'] ?? ''),
                comment: $this->filled($row['comment'] ?? null),
            ), $this->rows($request, 'items', 'name')),
            paymentMethod: $this->string($request, 'paymentMethod') ?? 'készpénz',
            currency: $this->string($request, 'currency') ?? 'HUF',
            payments: array_map(fn (array $row): ReceiptPayment => new ReceiptPayment(
                method: (string) ($row['method'] ?? ''),
                amount: (float) $row['amount'],
                description: $this->filled($row['description'] ?? null),
            ), $this->rows($request, 'payments', 'amount')),
            callId: $this->string($request, 'callId'),
            comment: $this->string($request, 'comment'),
            pdfTemplate: $this->string($request, 'pdfTemplate'),
            orderNumber: $this->string($request, 'orderNumber'),
            downloadPdf: $this->tristate($request, 'downloadPdf'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function registerPayment(SzamlaAgent $agent, Request $request): array
    {
        $agent->registerPayment(new PaymentRegistration(
            invoiceNumber: $this->required($request, 'invoiceNumber'),
            payments: array_map(fn (array $row): InvoicePayment => new InvoicePayment(
                date: $this->filled($row['date'] ?? null) !== null ? CarbonImmutable::parse((string) $row['date']) : CarbonImmutable::today(),
                method: (string) ($row['method'] ?? ''),
                amount: (float) $row['amount'],
                description: $this->filled($row['description'] ?? null),
            ), $this->rows($request, 'payments', 'amount')),
            additive: $request->boolean('additive'),
            taxNumber: $this->string($request, 'taxNumber'),
        ));

        return ['message' => 'A jóváírás rögzítve.'];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePdf(SzamlaAgent $agent, Request $request): array
    {
        $lookup = $this->invoiceLookup($request);
        $pdf = $agent->invoicePdf($lookup);

        return [
            'message' => 'PDF letöltve ('.number_format(strlen($pdf), 0, ',', ' ').' bájt).',
            'pdf' => $this->storePdf($lookup->invoiceNumber ?? $lookup->orderNumber ?? $lookup->externalId ?? 'szamla', $pdf),
        ];
    }

    private function invoiceLookup(Request $request): InvoiceLookup
    {
        return new InvoiceLookup(
            invoiceNumber: $this->string($request, 'invoiceNumber'),
            orderNumber: $this->string($request, 'orderNumber'),
            externalId: $this->string($request, 'externalId'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function result(InvoiceResult|InvoiceDetails|ReceiptResult $result, bool $withXml = false): array
    {
        $number = $result instanceof ReceiptResult ? $result->receiptNumber : $result->invoiceNumber;

        return array_filter([
            'message' => "Kész: {$number}",
            'data' => json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'pdf' => $result->hasPdf() ? $this->storePdf($number, (string) $result->pdf) : null,
            'xml' => $withXml && $result instanceof InvoiceDetails ? $this->prettyXml($this->truncateBase64($result->xml)) : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function done(mixed $_, string $message): array
    {
        return ['message' => $message];
    }

    /**
     * Repeater rows that have the given key filled; empty rows are skipped.
     *
     * @return list<array<string, mixed>>
     */
    private function rows(Request $request, string $name, string $key): array
    {
        $rows = $request->input($name, []);

        return array_values(array_filter(
            is_array($rows) ? $rows : [],
            static fn (mixed $row): bool => is_array($row) && filled($row[$key] ?? null),
        ));
    }

    private function string(Request $request, string $key): ?string
    {
        return $this->filled($request->input($key));
    }

    private function required(Request $request, string $key): string
    {
        return $this->string($request, $key) ?? throw new InvalidArgumentException("A(z) [{$key}] mező kötelező.");
    }

    private function filled(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    private function date(Request $request, string $key): ?CarbonImmutable
    {
        $value = $this->string($request, $key);

        return $value !== null ? CarbonImmutable::parse($value) : null;
    }

    private function tristate(Request $request, string $key): ?bool
    {
        $value = $this->string($request, $key);

        return $value === null ? null : $value === '1';
    }

    /**
     * Save a PDF under the workbench storage and return its viewer URL.
     */
    private function storePdf(string $name, string $pdf): string
    {
        $directory = storage_path(self::PDF_DIRECTORY);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = Str::slug($name).'-'.now()->format('YmdHis').'.pdf';
        file_put_contents($directory.'/'.$file, $pdf);

        return route('szamlazz.pdf', $file);
    }

    /**
     * Pull the XML document out of the multipart request body and hide the credentials.
     */
    private function requestXml(string $body): string
    {
        if (preg_match('/<\?xml.*>/s', $body, $match) !== 1) {
            return $body;
        }

        $xml = preg_replace_callback(
            '#<(szamlaagentkulcs|jelszo)>(.*?)</\1>#s',
            fn (array $parts): string => "<{$parts[1]}>{$this->mask($parts[2])}</{$parts[1]}>",
            $match[0],
        ) ?? $match[0];

        return $this->prettyXml($xml);
    }

    private function responseBody(string $body): string
    {
        if (str_starts_with($body, '%PDF')) {
            return '[PDF dokumentum, '.number_format(strlen($body), 0, ',', ' ').' bájt]';
        }

        return $this->prettyXml($this->truncateBase64($body));
    }

    private function truncateBase64(string $xml): string
    {
        return preg_replace_callback(
            '#<(pdf|nyugtaPdf)>([^<]{200,})</\1>#',
            static fn (array $parts): string => "<{$parts[1]}>[base64, ".number_format(strlen($parts[2]), 0, ',', ' ')." karakter]</{$parts[1]}>",
            $xml,
        ) ?? $xml;
    }

    private function prettyXml(string $xml): string
    {
        $document = new DOMDocument;
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        if (trim($xml) === '' || @$document->loadXML($xml) === false) {
            return $xml;
        }

        return (string) $document->saveXML();
    }

    private function mask(string $secret): string
    {
        return strlen($secret) <= 8 ? str_repeat('•', strlen($secret)) : substr($secret, 0, 4).'…'.substr($secret, -4);
    }
}

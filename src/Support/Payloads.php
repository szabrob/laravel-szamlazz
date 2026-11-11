<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Support;

use InvalidArgumentException;
use Szabrob\LaravelSzamlazz\Data\Credentials;
use Szabrob\LaravelSzamlazz\Data\Invoice\Invoice;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceItem;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceLookup;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoicePayment;
use Szabrob\LaravelSzamlazz\Data\Invoice\InvoiceReversal;
use Szabrob\LaravelSzamlazz\Data\Invoice\PaymentRegistration;
use Szabrob\LaravelSzamlazz\Data\Receipt\Receipt;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptEmail;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptItem;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptLookup;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptPayment;
use Szabrob\LaravelSzamlazz\Data\Receipt\ReceiptReversal;
use Szabrob\LaravelSzamlazz\Enums\Action;
use Szabrob\LaravelSzamlazz\Enums\InvoiceType;

/**
 * @internal Maps package DTOs to Számla Agent request XML, in XSD element order.
 */
final class Payloads
{
    /** Response version 2: XML response including the base64 encoded PDF. */
    private const int XML_RESPONSE = 2;

    /** Response version 1: plain text or raw PDF response. */
    private const int TEXT_RESPONSE = 1;

    public function __construct(
        private readonly Credentials $credentials,
        private readonly bool $eInvoice,
        private readonly bool $downloadPdf,
        private readonly ?string $aggregator,
    ) {}

    public function invoice(Invoice $invoice): string
    {
        $seller = $invoice->seller;
        $buyer = $invoice->buyer;

        return $this->xml(Action::CreateInvoice, [
            'beallitasok' => [
                ...$this->auth(),
                'eszamla' => $invoice->eInvoice ?? $this->eInvoice,
                'szamlaLetoltes' => $invoice->downloadPdf ?? $this->downloadPdf,
                'valaszVerzio' => self::XML_RESPONSE,
                'aggregator' => $this->aggregator,
                'szamlaKulsoAzon' => $invoice->externalId,
            ],
            'fejlec' => [
                'keltDatum' => $invoice->issueDate,
                'teljesitesDatum' => $invoice->fulfillmentDate,
                'fizetesiHataridoDatum' => $invoice->dueDate,
                'fizmod' => $invoice->paymentMethod,
                'penznem' => $invoice->currency,
                'szamlaNyelve' => $invoice->language,
                'megjegyzes' => $invoice->comment,
                'arfolyamBank' => $invoice->exchangeRateBank,
                'arfolyam' => $invoice->exchangeRate,
                'rendelesSzam' => $invoice->orderNumber,
                'dijbekeroSzamlaszam' => $invoice->proformaNumber,
                'elolegszamla' => $this->flag($invoice, InvoiceType::Prepayment),
                'vegszamla' => $this->flag($invoice, InvoiceType::Final),
                'elolegSzamlaszam' => $invoice->prepaymentInvoiceNumber,
                'helyesbitoszamla' => $this->flag($invoice, InvoiceType::Corrective),
                'helyesbitettSzamlaszam' => $invoice->correctedInvoiceNumber,
                'dijbekero' => $this->flag($invoice, InvoiceType::Proforma),
                'szallitolevel' => $this->flag($invoice, InvoiceType::DeliveryNote),
                'logoExtra' => $invoice->logoExtra,
                'szamlaszamElotag' => $invoice->prefix,
                'fizetendoKorrekcio' => $invoice->paymentCorrection,
                'fizetve' => $invoice->paid,
                'arresAfa' => $invoice->marginScheme,
                'eusAfa' => $invoice->euVat,
                'szamlaSablon' => $invoice->template,
                'elonezetpdf' => $invoice->preview,
            ],
            'elado' => [
                'bank' => $seller?->bank,
                'bankszamlaszam' => $seller?->bankAccount,
                'emailReplyto' => $seller?->emailReplyTo,
                'emailTargy' => $seller?->emailSubject,
                'emailSzoveg' => $seller?->emailBody,
                'alairoNeve' => $seller?->signatoryName,
            ],
            'vevo' => [
                'nev' => $buyer->name,
                'orszag' => $buyer->country,
                'irsz' => $buyer->postCode,
                'telepules' => $buyer->city,
                'cim' => $buyer->address,
                'email' => $buyer->email,
                'sendEmail' => $buyer->sendEmail,
                'adoalany' => $buyer->taxPayerType,
                'adoszam' => $buyer->taxNumber,
                'csoportazonosito' => $buyer->groupTaxNumber,
                'adoszamEU' => $buyer->euTaxNumber,
                'postazasiNev' => $buyer->postalName,
                'postazasiOrszag' => $buyer->postalCountry,
                'postazasiIrsz' => $buyer->postalPostCode,
                'postazasiTelepules' => $buyer->postalCity,
                'postazasiCim' => $buyer->postalAddress,
                'azonosito' => $buyer->identifier,
                'alairoNeve' => $buyer->signatoryName,
                'telefonszam' => $buyer->phone,
                'megjegyzes' => $buyer->comment,
            ],
            'tetelek' => [
                'tetel' => array_map($this->invoiceItem(...), array_values($invoice->items)),
            ],
        ]);
    }

    public function invoiceReversal(InvoiceReversal $reversal): string
    {
        return $this->xml(Action::ReverseInvoice, [
            'beallitasok' => [
                ...$this->auth(),
                'eszamla' => $reversal->eInvoice ?? $this->eInvoice,
                'szamlaLetoltes' => $reversal->downloadPdf ?? $this->downloadPdf,
                'aggregator' => $this->aggregator,
                'valaszVerzio' => self::XML_RESPONSE,
                'szamlaKulsoAzon' => $reversal->externalId,
            ],
            'fejlec' => [
                'szamlaszam' => $reversal->invoiceNumber,
                'keltDatum' => $reversal->issueDate,
                'teljesitesDatum' => $reversal->fulfillmentDate,
                'megjegyzes' => $reversal->comment,
                'szamlaSablon' => $reversal->template,
            ],
            'elado' => [
                'emailReplyto' => $reversal->emailReplyTo,
                'emailTargy' => $reversal->emailSubject,
                'emailSzoveg' => $reversal->emailBody,
            ],
            'vevo' => [
                'email' => $reversal->buyerEmail,
                'adoszam' => $reversal->buyerTaxNumber,
                'adoszamEU' => $reversal->buyerEuTaxNumber,
            ],
        ]);
    }

    public function paymentRegistration(PaymentRegistration $registration): string
    {
        if (count($registration->payments) > PaymentRegistration::MAX_PAYMENTS) {
            throw new InvalidArgumentException(sprintf(
                'Számla Agent accepts at most %d payments per registration, %d given.',
                PaymentRegistration::MAX_PAYMENTS,
                count($registration->payments),
            ));
        }

        return $this->xml(Action::RegisterPayment, [
            'beallitasok' => [
                ...$this->auth(),
                'szamlaszam' => $registration->invoiceNumber,
                'adoszam' => $registration->taxNumber,
                'additiv' => $registration->additive,
                'aggregator' => $this->aggregator,
                'valaszVerzio' => self::TEXT_RESPONSE,
            ],
            'kifizetes' => array_map(static fn (InvoicePayment $payment): array => [
                'datum' => $payment->date,
                'jogcim' => $payment->method,
                'osszeg' => $payment->amount,
                'leiras' => $payment->description,
            ], array_values($registration->payments)),
        ]);
    }

    public function invoicePdf(InvoiceLookup $lookup): string
    {
        return $this->xml(Action::InvoicePdf, [
            ...$this->auth(),
            ...$this->invoiceLookup($lookup),
            'valaszVerzio' => self::TEXT_RESPONSE,
        ]);
    }

    public function invoiceData(InvoiceLookup $lookup, bool $withPdf): string
    {
        $identifiers = $this->invoiceLookup($lookup);

        return $this->xml(Action::InvoiceData, [
            ...$this->auth(),
            'szamlaszam' => $identifiers['szamlaszam'],
            'rendelesSzam' => $identifiers['rendelesSzam'],
            'pdf' => $withPdf,
            'szamlaKulsoAzon' => $identifiers['szamlaKulsoAzon'],
        ]);
    }

    public function deleteProforma(InvoiceLookup $lookup): string
    {
        if ($lookup->externalId !== null || ($lookup->invoiceNumber === null && $lookup->orderNumber === null)) {
            throw new InvalidArgumentException('A proforma can only be deleted by invoice number or order number.');
        }

        return $this->xml(Action::DeleteProforma, [
            'beallitasok' => $this->auth(),
            'fejlec' => [
                'szamlaszam' => $lookup->invoiceNumber,
                'rendelesszam' => $lookup->orderNumber,
            ],
        ]);
    }

    public function receipt(Receipt $receipt): string
    {
        return $this->xml(Action::CreateReceipt, [
            'beallitasok' => [
                ...$this->auth(),
                'pdfLetoltes' => $receipt->downloadPdf ?? $this->downloadPdf,
            ],
            'fejlec' => [
                'hivasAzonosito' => $receipt->callId,
                'elotag' => $receipt->prefix,
                'fizmod' => $receipt->paymentMethod,
                'penznem' => $receipt->currency,
                'devizaarf' => $receipt->exchangeRate,
                'devizabank' => $receipt->exchangeRateBank,
                'megjegyzes' => $receipt->comment,
                'pdfSablon' => $receipt->pdfTemplate,
                'fokonyvVevo' => $receipt->ledgerBuyer,
                'rendelesSzam' => $receipt->orderNumber,
            ],
            'tetelek' => [
                'tetel' => array_map($this->receiptItem(...), array_values($receipt->items)),
            ],
            'kifizetesek' => $receipt->payments === [] ? null : [
                'kifizetes' => array_map(static fn (ReceiptPayment $payment): array => [
                    'fizetoeszkoz' => $payment->method,
                    'osszeg' => $payment->amount,
                    'leiras' => $payment->description,
                ], array_values($receipt->payments)),
            ],
        ]);
    }

    public function receiptReversal(ReceiptReversal $reversal): string
    {
        return $this->xml(Action::ReverseReceipt, [
            'beallitasok' => [
                ...$this->auth(),
                'pdfLetoltes' => $reversal->downloadPdf ?? $this->downloadPdf,
            ],
            'fejlec' => [
                'nyugtaszam' => $reversal->receiptNumber,
                'pdfSablon' => $reversal->pdfTemplate,
                'hivasAzonosito' => $reversal->callId,
            ],
        ]);
    }

    public function receiptLookup(ReceiptLookup $lookup): string
    {
        if ($lookup->receiptNumber === null && $lookup->orderNumber === null && $lookup->callId === null) {
            throw new InvalidArgumentException('A receipt lookup needs a receipt number, order number or call id.');
        }

        return $this->xml(Action::GetReceipt, [
            'beallitasok' => [
                ...$this->auth(),
                'pdfLetoltes' => $lookup->downloadPdf ?? $this->downloadPdf,
            ],
            'fejlec' => [
                'nyugtaszam' => $lookup->receiptNumber,
                'rendelesSzam' => $lookup->orderNumber,
                'hivasAzonosito' => $lookup->callId,
                'pdfSablon' => $lookup->pdfTemplate,
            ],
        ]);
    }

    public function receiptEmail(ReceiptEmail $email): string
    {
        return $this->xml(Action::SendReceipt, [
            'beallitasok' => $this->auth(),
            'fejlec' => [
                'nyugtaszam' => $email->receiptNumber,
            ],
            'emailKuldes' => [
                'email' => $email->email,
                'emailReplyto' => $email->replyTo,
                'emailTargy' => $email->subject,
                'emailSzoveg' => $email->body,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceItem(InvoiceItem $item): array
    {
        $amounts = Amounts::resolve($item->quantity, $item->netUnitPrice, $item->vatRate, $item->netAmount, $item->vatAmount, $item->grossAmount);

        return [
            'megnevezes' => $item->name,
            'azonosito' => $item->identifier,
            'mennyiseg' => $item->quantity,
            'mennyisegiEgyseg' => $item->unit,
            'nettoEgysegar' => $item->netUnitPrice,
            'afakulcs' => $item->vatRate,
            'arresAfaAlap' => $item->marginSchemeBase,
            'nettoErtek' => $amounts['net'],
            'afaErtek' => $amounts['vat'],
            'bruttoErtek' => $amounts['gross'],
            'megjegyzes' => $item->comment,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function receiptItem(ReceiptItem $item): array
    {
        $amounts = Amounts::resolve($item->quantity, $item->netUnitPrice, $item->vatRate, $item->netAmount, $item->vatAmount, $item->grossAmount);

        return [
            'megnevezes' => $item->name,
            'azonosito' => $item->identifier,
            'mennyiseg' => $item->quantity,
            'mennyisegiEgyseg' => $item->unit,
            'nettoEgysegar' => $item->netUnitPrice,
            'afakulcs' => $item->vatRate,
            'netto' => $amounts['net'],
            'afa' => $amounts['vat'],
            'brutto' => $amounts['gross'],
            'megjegyzes' => $item->comment,
        ];
    }

    /**
     * @return array{szamlaszam: ?string, rendelesSzam: ?string, szamlaKulsoAzon: ?string}
     */
    private function invoiceLookup(InvoiceLookup $lookup): array
    {
        if ($lookup->invoiceNumber === null && $lookup->orderNumber === null && $lookup->externalId === null) {
            throw new InvalidArgumentException('An invoice lookup needs an invoice number, order number or external id.');
        }

        return [
            'szamlaszam' => $lookup->invoiceNumber,
            'rendelesSzam' => $lookup->orderNumber,
            'szamlaKulsoAzon' => $lookup->externalId,
        ];
    }

    /**
     * @return array<string, ?string>
     */
    private function auth(): array
    {
        if (filled($this->credentials->apiKey)) {
            return ['szamlaagentkulcs' => $this->credentials->apiKey];
        }

        return [
            'felhasznalo' => $this->credentials->username,
            'jelszo' => $this->credentials->password,
        ];
    }

    private function flag(Invoice $invoice, InvoiceType $type): ?bool
    {
        return $invoice->type === $type ? true : null;
    }

    /**
     * @param  array<string, mixed>  $tree
     */
    private function xml(Action $action, array $tree): string
    {
        return XmlBuilder::build($action->rootElement(), $action->namespace(), $tree);
    }
}

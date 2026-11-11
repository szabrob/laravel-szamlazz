<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Facades;

use Illuminate\Support\Facades\Facade;
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

/**
 * @method static \Szabrob\LaravelSzamlazz\SzamlaAgent withCredentials(Credentials $credentials)
 * @method static InvoiceResult createInvoice(Invoice $invoice)
 * @method static InvoiceResult reverseInvoice(InvoiceReversal $reversal)
 * @method static void registerPayment(PaymentRegistration $registration)
 * @method static string invoicePdf(InvoiceLookup $lookup)
 * @method static InvoiceDetails invoice(InvoiceLookup $lookup, bool $withPdf = false)
 * @method static void deleteProforma(InvoiceLookup $lookup)
 * @method static ReceiptResult createReceipt(Receipt $receipt)
 * @method static ReceiptResult reverseReceipt(ReceiptReversal $reversal)
 * @method static ReceiptResult receipt(ReceiptLookup $lookup)
 * @method static void sendReceipt(ReceiptEmail $email)
 *
 * @see \Szabrob\LaravelSzamlazz\SzamlaAgent
 */
class SzamlaAgent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Szabrob\LaravelSzamlazz\SzamlaAgent::class;
    }
}

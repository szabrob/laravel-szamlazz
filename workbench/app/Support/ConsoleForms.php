<?php

declare(strict_types=1);

namespace Workbench\App\Support;

use BackedEnum;
use Szabrob\LaravelSzamlazz\Enums\InvoiceTemplate;
use Szabrob\LaravelSzamlazz\Enums\InvoiceType;
use Szabrob\LaravelSzamlazz\Enums\Language;
use Szabrob\LaravelSzamlazz\Enums\TaxPayerType;
use UnitEnum;

/**
 * Form definitions of the workbench test console, one per SzamlaAgent method.
 *
 * Field names use dot notation (`buyer.name`); repeaters hold rows of fields
 * (`items.0.name`). Field types: text, email, number, date, textarea, select,
 * checkbox and tristate (a nullable bool: config default / yes / no).
 */
final class ConsoleForms
{
    /**
     * @return array<string, array{group: string, title: string, method: string, description: string, sections: array<string, list<array<string, mixed>>>, repeaters?: array<string, array{title: string, rows: int, max?: int, fields: list<array<string, mixed>>}>}>
     */
    public static function all(): array
    {
        $today = now()->format('Y-m-d');
        $due = now()->addDays(8)->format('Y-m-d');

        return [
            'create-invoice' => [
                'group' => 'Számla',
                'title' => 'Számla készítése',
                'method' => 'createInvoice(Invoice)',
                'description' => 'Számla, díjbekérő, előleg-, vég-, helyesbítő számla vagy szállítólevél készítése.',
                'sections' => [
                    'Fejléc' => [
                        self::select('type', 'Típus', self::enumOptions(InvoiceType::class), InvoiceType::Invoice->value),
                        self::date('issueDate', 'Kelt (üres = ma)'),
                        self::date('fulfillmentDate', 'Teljesítés', $today),
                        self::date('dueDate', 'Fizetési határidő', $due),
                        self::text('paymentMethod', 'Fizetési mód', 'Átutalás'),
                        self::text('currency', 'Pénznem', 'HUF'),
                        self::select('language', 'Nyelv', self::enumOptions(Language::class), Language::Hungarian->value),
                        self::text('prefix', 'Számlaszám előtag'),
                        self::text('orderNumber', 'Rendelésszám'),
                        self::text('externalId', 'Külső azonosító'),
                        self::text('proformaNumber', 'Díjbekérő száma (végszámlához)'),
                        self::text('prepaymentInvoiceNumber', 'Előlegszámla száma'),
                        self::text('correctedInvoiceNumber', 'Helyesbített számla száma'),
                        self::select('template', 'Sablon', ['' => '— alapértelmezett —'] + self::enumOptions(InvoiceTemplate::class)),
                        self::textarea('comment', 'Megjegyzés'),
                        self::checkbox('paid', 'Fizetve'),
                        self::checkbox('preview', 'Csak előnézet'),
                        self::tristate('eInvoice', 'E-számla'),
                        self::tristate('downloadPdf', 'PDF letöltése'),
                    ],
                    'Vevő' => [
                        self::text('buyer.name', 'Név', 'Teszt Vevő Kft.'),
                        self::text('buyer.postCode', 'Irányítószám', '1111'),
                        self::text('buyer.city', 'Város', 'Budapest'),
                        self::text('buyer.address', 'Cím', 'Teszt utca 1.'),
                        self::text('buyer.country', 'Ország'),
                        self::email('buyer.email', 'E-mail'),
                        self::checkbox('buyer.sendEmail', 'E-mail küldése a vevőnek'),
                        self::select('buyer.taxPayerType', 'Adóalany', ['' => '— nincs megadva —'] + self::enumOptions(TaxPayerType::class)),
                        self::text('buyer.taxNumber', 'Adószám'),
                        self::text('buyer.euTaxNumber', 'Közösségi adószám'),
                        self::text('buyer.phone', 'Telefon'),
                    ],
                    'Eladó (opcionális)' => [
                        self::text('seller.bank', 'Bank'),
                        self::text('seller.bankAccount', 'Bankszámlaszám'),
                        self::email('seller.emailReplyTo', 'Válaszcím'),
                        self::text('seller.emailSubject', 'E-mail tárgy'),
                        self::textarea('seller.emailBody', 'E-mail szöveg'),
                    ],
                ],
                'repeaters' => [
                    'items' => self::itemsRepeater(),
                ],
            ],
            'reverse-invoice' => [
                'group' => 'Számla',
                'title' => 'Számla sztornó',
                'method' => 'reverseInvoice(InvoiceReversal)',
                'description' => 'Egy meglévő számla sztornózása.',
                'sections' => [
                    'Sztornó' => [
                        self::text('invoiceNumber', 'Számlaszám', required: true),
                        self::date('issueDate', 'Kelt'),
                        self::date('fulfillmentDate', 'Teljesítés'),
                        self::text('externalId', 'Külső azonosító'),
                        self::email('buyerEmail', 'Vevő e-mail'),
                        self::textarea('comment', 'Megjegyzés'),
                        self::tristate('eInvoice', 'E-számla'),
                        self::tristate('downloadPdf', 'PDF letöltése'),
                    ],
                ],
            ],
            'register-payment' => [
                'group' => 'Számla',
                'title' => 'Jóváírás rögzítése',
                'method' => 'registerPayment(PaymentRegistration)',
                'description' => 'Kifizetés rögzítése egy számlán (legfeljebb 5 tétel).',
                'sections' => [
                    'Számla' => [
                        self::text('invoiceNumber', 'Számlaszám', required: true),
                        self::text('taxNumber', 'Adószám'),
                        self::checkbox('additive', 'Hozzáadás (kikapcsolva: felülírja a korábbiakat)', true),
                    ],
                ],
                'repeaters' => [
                    'payments' => [
                        'title' => 'Jóváírások',
                        'rows' => 1,
                        'max' => 5,
                        'fields' => [
                            self::date('date', 'Dátum', $today),
                            self::text('method', 'Mód', 'Átutalás'),
                            self::number('amount', 'Összeg'),
                            self::text('description', 'Leírás'),
                        ],
                    ],
                ],
            ],
            'invoice-pdf' => [
                'group' => 'Számla',
                'title' => 'Számla PDF',
                'method' => 'invoicePdf(InvoiceLookup)',
                'description' => 'Egy meglévő számla PDF-jének letöltése. Egy azonosító elég.',
                'sections' => ['Keresés' => self::invoiceLookupFields()],
            ],
            'invoice-data' => [
                'group' => 'Számla',
                'title' => 'Számla adatai',
                'method' => 'invoice(InvoiceLookup, withPdf)',
                'description' => 'Számla adatainak lekérdezése XML-ben. Egy azonosító elég.',
                'sections' => [
                    'Keresés' => [
                        ...self::invoiceLookupFields(),
                        self::checkbox('withPdf', 'PDF is kérem'),
                    ],
                ],
            ],
            'delete-proforma' => [
                'group' => 'Számla',
                'title' => 'Díjbekérő törlése',
                'method' => 'deleteProforma(InvoiceLookup)',
                'description' => 'Díjbekérő törlése számlaszám vagy rendelésszám alapján.',
                'sections' => [
                    'Keresés' => [
                        self::text('invoiceNumber', 'Díjbekérő száma'),
                        self::text('orderNumber', 'Rendelésszám'),
                    ],
                ],
            ],
            'create-receipt' => [
                'group' => 'Nyugta',
                'title' => 'Nyugta készítése',
                'method' => 'createReceipt(Receipt)',
                'description' => 'Nyugta kiállítása.',
                'sections' => [
                    'Fejléc' => [
                        self::text('prefix', 'Előtag', 'NYGT', required: true),
                        self::text('paymentMethod', 'Fizetési mód', 'készpénz'),
                        self::text('currency', 'Pénznem', 'HUF'),
                        self::text('callId', 'Hívásazonosító'),
                        self::text('orderNumber', 'Rendelésszám'),
                        self::text('pdfTemplate', 'PDF sablon'),
                        self::textarea('comment', 'Megjegyzés'),
                        self::tristate('downloadPdf', 'PDF letöltése'),
                    ],
                ],
                'repeaters' => [
                    'items' => self::itemsRepeater(),
                    'payments' => [
                        'title' => 'Jóváírások (opcionális)',
                        'rows' => 0,
                        'fields' => [
                            self::text('method', 'Mód', 'készpénz'),
                            self::number('amount', 'Összeg'),
                            self::text('description', 'Leírás'),
                        ],
                    ],
                ],
            ],
            'reverse-receipt' => [
                'group' => 'Nyugta',
                'title' => 'Nyugta sztornó',
                'method' => 'reverseReceipt(ReceiptReversal)',
                'description' => 'Egy meglévő nyugta sztornózása.',
                'sections' => [
                    'Sztornó' => [
                        self::text('receiptNumber', 'Nyugtaszám', required: true),
                        self::text('callId', 'Hívásazonosító'),
                        self::text('pdfTemplate', 'PDF sablon'),
                        self::tristate('downloadPdf', 'PDF letöltése'),
                    ],
                ],
            ],
            'get-receipt' => [
                'group' => 'Nyugta',
                'title' => 'Nyugta lekérdezése',
                'method' => 'receipt(ReceiptLookup)',
                'description' => 'Nyugta lekérdezése. Egy azonosító elég.',
                'sections' => [
                    'Keresés' => [
                        self::text('receiptNumber', 'Nyugtaszám'),
                        self::text('orderNumber', 'Rendelésszám'),
                        self::text('callId', 'Hívásazonosító'),
                        self::text('pdfTemplate', 'PDF sablon'),
                        self::tristate('downloadPdf', 'PDF letöltése'),
                    ],
                ],
            ],
            'send-receipt' => [
                'group' => 'Nyugta',
                'title' => 'Nyugta küldése',
                'method' => 'sendReceipt(ReceiptEmail)',
                'description' => 'Egy meglévő nyugta elküldése e-mailben.',
                'sections' => [
                    'E-mail' => [
                        self::text('receiptNumber', 'Nyugtaszám', required: true),
                        self::email('email', 'Címzett', required: true),
                        self::email('replyTo', 'Válaszcím'),
                        self::text('subject', 'Tárgy'),
                        self::textarea('body', 'Szöveg'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Turn a dot notation field name into an HTML input name: `items.0.name` → `items[0][name]`.
     */
    public static function inputName(string $name): string
    {
        $parts = explode('.', $name);

        return array_shift($parts).implode('', array_map(static fn (string $part): string => "[{$part}]", $parts));
    }

    /**
     * @return array{title: string, rows: int, fields: list<array<string, mixed>>}
     */
    private static function itemsRepeater(): array
    {
        return [
            'title' => 'Tételek',
            'rows' => 1,
            'fields' => [
                self::text('name', 'Megnevezés', 'Teszt termék'),
                self::number('quantity', 'Mennyiség', '1'),
                self::text('unit', 'Egység', 'db'),
                self::number('netUnitPrice', 'Nettó egységár', '1000'),
                self::text('vatRate', 'ÁFA (27, 5.5, AAM, TAM…)', '27'),
                self::text('comment', 'Megjegyzés'),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function invoiceLookupFields(): array
    {
        return [
            self::text('invoiceNumber', 'Számlaszám'),
            self::text('orderNumber', 'Rendelésszám'),
            self::text('externalId', 'Külső azonosító'),
        ];
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     * @return array<string, string>
     */
    private static function enumOptions(string $enum): array
    {
        $options = [];

        foreach ($enum::cases() as $case) {
            $value = $case instanceof BackedEnum ? (string) $case->value : $case->name;
            $options[$value] = "{$case->name} ({$value})";
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private static function text(string $name, string $label, ?string $default = null, bool $required = false): array
    {
        return ['type' => 'text', 'name' => $name, 'label' => $label, 'default' => $default, 'required' => $required];
    }

    /**
     * @return array<string, mixed>
     */
    private static function email(string $name, string $label, bool $required = false): array
    {
        return ['type' => 'email', 'name' => $name, 'label' => $label, 'default' => null, 'required' => $required];
    }

    /**
     * @return array<string, mixed>
     */
    private static function number(string $name, string $label, ?string $default = null): array
    {
        return ['type' => 'number', 'name' => $name, 'label' => $label, 'default' => $default, 'required' => false];
    }

    /**
     * @return array<string, mixed>
     */
    private static function date(string $name, string $label, ?string $default = null): array
    {
        return ['type' => 'date', 'name' => $name, 'label' => $label, 'default' => $default, 'required' => false];
    }

    /**
     * @return array<string, mixed>
     */
    private static function textarea(string $name, string $label): array
    {
        return ['type' => 'textarea', 'name' => $name, 'label' => $label, 'default' => null, 'required' => false];
    }

    /**
     * @param  array<string, string>  $options
     * @return array<string, mixed>
     */
    private static function select(string $name, string $label, array $options, ?string $default = null): array
    {
        return ['type' => 'select', 'name' => $name, 'label' => $label, 'options' => $options, 'default' => $default, 'required' => false];
    }

    /**
     * @return array<string, mixed>
     */
    private static function checkbox(string $name, string $label, bool $default = false): array
    {
        return ['type' => 'checkbox', 'name' => $name, 'label' => $label, 'default' => $default, 'required' => false];
    }

    /**
     * @return array<string, mixed>
     */
    private static function tristate(string $name, string $label): array
    {
        return self::select($name, $label, ['' => '— config szerint —', '1' => 'Igen', '0' => 'Nem']);
    }
}

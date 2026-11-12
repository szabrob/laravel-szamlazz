<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Results;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;
use Szabrob\LaravelSzamlazz\Data\Results\Concerns\HasPdf;

/**
 * Invoice data returned by the invoice XML query. The complete response is
 * available in `$xml` for fields not mapped here.
 */
final class InvoiceDetails extends Data
{
    use HasPdf;

    public function __construct(
        public string $invoiceNumber,
        public ?int $id = null,
        public ?string $type = null,
        public ?CarbonImmutable $issueDate = null,
        public ?CarbonImmutable $fulfillmentDate = null,
        public ?CarbonImmutable $dueDate = null,
        public ?string $paymentMethod = null,
        public ?string $currency = null,
        public ?string $buyerName = null,
        public ?string $buyerTaxNumber = null,
        public ?string $buyerEmail = null,
        public ?float $netTotal = null,
        public ?float $vatTotal = null,
        public ?float $grossTotal = null,
        #[Hidden]
        public ?string $pdf = null,
        #[Hidden]
        public string $xml = '',
    ) {}
}

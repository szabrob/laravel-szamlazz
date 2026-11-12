<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Results;

use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;
use Szabrob\LaravelSzamlazz\Data\Results\Concerns\HasPdf;

/**
 * Result of an invoice creation or reversal.
 */
final class InvoiceResult extends Data
{
    use HasPdf;

    public function __construct(
        public string $invoiceNumber,
        public ?int $id = null,
        public ?float $netTotal = null,
        public ?float $grossTotal = null,
        public ?float $outstandingAmount = null,
        public ?string $customerAccountUrl = null,
        /** Raw PDF bytes, hidden from array and JSON output. */
        #[Hidden]
        public ?string $pdf = null,
    ) {}
}

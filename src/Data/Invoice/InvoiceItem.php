<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Invoice;

use Spatie\LaravelData\Data;

/**
 * An invoice line. Missing net, VAT and gross amounts are calculated from the
 * quantity, net unit price and a numeric VAT rate (non-numeric rates such as
 * "AAM" or "TAM" calculate zero VAT).
 */
final class InvoiceItem extends Data
{
    public function __construct(
        public string $name,
        public float $quantity,
        public string $unit,
        public float $netUnitPrice,
        public string $vatRate,
        public ?string $identifier = null,
        public ?float $netAmount = null,
        public ?float $vatAmount = null,
        public ?float $grossAmount = null,
        public ?string $comment = null,
        public ?float $marginSchemeBase = null,
    ) {}
}

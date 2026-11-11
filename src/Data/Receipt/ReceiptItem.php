<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Receipt;

use Spatie\LaravelData\Data;

/**
 * A receipt line. Missing net, VAT and gross amounts are calculated from the
 * quantity, net unit price and a numeric VAT rate.
 */
final class ReceiptItem extends Data
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
    ) {}
}

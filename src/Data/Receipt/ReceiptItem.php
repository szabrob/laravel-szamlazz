<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Receipt;

use Spatie\LaravelData\Data;

/**
 * A receipt line. Missing net, VAT and gross amounts are derived from the given
 * ones (so net + VAT always equals gross), falling back to quantity × net
 * unit price and the VAT rate. The VAT rate is a percentage such as "27" or
 * "5.5", or a Számla Agent VAT key such as "AAM" or "TAM" (zero VAT); any
 * other value, and three given amounts that do not add up, throw an
 * InvalidSzamlazzRequest before the request is sent.
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

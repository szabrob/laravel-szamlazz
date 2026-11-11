<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Receipt;

use Spatie\LaravelData\Data;

final class ReceiptPayment extends Data
{
    public function __construct(
        public string $method,
        public float $amount,
        public ?string $description = null,
    ) {}
}

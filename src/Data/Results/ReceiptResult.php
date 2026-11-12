<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Results;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;
use Szabrob\LaravelSzamlazz\Data\Results\Concerns\HasPdf;

/**
 * Result of a receipt creation, reversal or query.
 */
final class ReceiptResult extends Data
{
    use HasPdf;

    public function __construct(
        public string $receiptNumber,
        public ?int $id = null,
        public ?string $callId = null,
        public ?string $type = null,
        public bool $reversed = false,
        public ?string $reversedReceiptNumber = null,
        public ?CarbonImmutable $issueDate = null,
        public ?string $paymentMethod = null,
        public ?string $currency = null,
        public ?float $netTotal = null,
        public ?float $vatTotal = null,
        public ?float $grossTotal = null,
        #[Hidden]
        public ?string $pdf = null,
    ) {}
}

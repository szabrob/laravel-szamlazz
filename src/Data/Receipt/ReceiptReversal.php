<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Receipt;

use Spatie\LaravelData\Data;

final class ReceiptReversal extends Data
{
    public function __construct(
        public string $receiptNumber,
        public ?string $callId = null,
        public ?string $pdfTemplate = null,
        public ?bool $downloadPdf = null,
    ) {}
}

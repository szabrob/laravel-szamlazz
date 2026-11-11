<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Receipt;

use Spatie\LaravelData\Data;

/**
 * Identifies an existing receipt by receipt number, order number or call id.
 */
final class ReceiptLookup extends Data
{
    public function __construct(
        public ?string $receiptNumber = null,
        public ?string $orderNumber = null,
        public ?string $callId = null,
        public ?string $pdfTemplate = null,
        public ?bool $downloadPdf = null,
    ) {}

    public static function byReceiptNumber(string $receiptNumber): self
    {
        return new self(receiptNumber: $receiptNumber);
    }

    public static function byOrderNumber(string $orderNumber): self
    {
        return new self(orderNumber: $orderNumber);
    }

    public static function byCallId(string $callId): self
    {
        return new self(callId: $callId);
    }
}

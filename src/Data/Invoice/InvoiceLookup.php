<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Invoice;

use Spatie\LaravelData\Data;

/**
 * Identifies an existing invoice by invoice number, order number or external id.
 */
final class InvoiceLookup extends Data
{
    public function __construct(
        public ?string $invoiceNumber = null,
        public ?string $orderNumber = null,
        public ?string $externalId = null,
    ) {}

    public static function byInvoiceNumber(string $invoiceNumber): self
    {
        return new self(invoiceNumber: $invoiceNumber);
    }

    public static function byOrderNumber(string $orderNumber): self
    {
        return new self(orderNumber: $orderNumber);
    }

    public static function byExternalId(string $externalId): self
    {
        return new self(externalId: $externalId);
    }
}

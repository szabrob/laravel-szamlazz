<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Invoice;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

/**
 * Register payments (jóváírás) on an invoice. With `additive: false` the
 * given payments replace the previously registered ones.
 */
final class PaymentRegistration extends Data
{
    /**
     * @param  array<int, InvoicePayment>  $payments
     */
    public function __construct(
        public string $invoiceNumber,
        #[DataCollectionOf(InvoicePayment::class), Max(5)]
        public array $payments,
        public bool $additive = true,
        public ?string $taxNumber = null,
    ) {}
}

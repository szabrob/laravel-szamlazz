<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Invoice;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

/**
 * Register payments (jóváírás) on an invoice. With `additive: false` the
 * given payments replace the previously registered ones.
 *
 * Számla Agent accepts at most MAX_PAYMENTS payments per request; more are
 * rejected with an InvalidArgumentException before anything is sent.
 */
final class PaymentRegistration extends Data
{
    public const int MAX_PAYMENTS = 5;

    /**
     * @param  array<int, InvoicePayment>  $payments
     */
    public function __construct(
        public string $invoiceNumber,
        #[DataCollectionOf(InvoicePayment::class), Max(self::MAX_PAYMENTS)]
        public array $payments,
        public bool $additive = true,
        public ?string $taxNumber = null,
    ) {}
}

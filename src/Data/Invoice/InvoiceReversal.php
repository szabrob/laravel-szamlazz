<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Invoice;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Szabrob\LaravelSzamlazz\Enums\InvoiceTemplate;

/**
 * Reverse (storno) an existing invoice.
 */
final class InvoiceReversal extends Data
{
    public function __construct(
        public string $invoiceNumber,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d', type: CarbonImmutable::class)]
        public ?DateTimeInterface $issueDate = null,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d', type: CarbonImmutable::class)]
        public ?DateTimeInterface $fulfillmentDate = null,
        public ?string $comment = null,
        public ?InvoiceTemplate $template = null,
        public ?string $buyerEmail = null,
        public ?string $buyerTaxNumber = null,
        public ?string $buyerEuTaxNumber = null,
        public ?string $emailReplyTo = null,
        public ?string $emailSubject = null,
        public ?string $emailBody = null,
        public ?string $externalId = null,
        public ?bool $eInvoice = null,
        public ?bool $downloadPdf = null,
    ) {}
}

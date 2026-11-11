<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Invoice;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Szabrob\LaravelSzamlazz\Enums\InvoiceTemplate;
use Szabrob\LaravelSzamlazz\Enums\InvoiceType;
use Szabrob\LaravelSzamlazz\Enums\Language;

final class Invoice extends Data
{
    /**
     * @param  array<int, InvoiceItem>  $items
     */
    public function __construct(
        public Buyer $buyer,
        #[DataCollectionOf(InvoiceItem::class)]
        public array $items,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d', type: CarbonImmutable::class)]
        public DateTimeInterface $fulfillmentDate,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d', type: CarbonImmutable::class)]
        public DateTimeInterface $dueDate,
        public string $paymentMethod = 'Átutalás',
        public string $currency = 'HUF',
        public Language $language = Language::Hungarian,
        public InvoiceType $type = InvoiceType::Invoice,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d', type: CarbonImmutable::class)]
        public ?DateTimeInterface $issueDate = null,
        public ?Seller $seller = null,
        public ?string $comment = null,
        public ?string $orderNumber = null,
        public ?string $exchangeRateBank = null,
        public ?float $exchangeRate = null,
        public ?string $proformaNumber = null,
        public ?string $prepaymentInvoiceNumber = null,
        public ?string $correctedInvoiceNumber = null,
        public ?string $prefix = null,
        public ?string $logoExtra = null,
        public ?float $paymentCorrection = null,
        public ?bool $paid = null,
        public ?bool $marginScheme = null,
        public ?bool $euVat = null,
        public ?InvoiceTemplate $template = null,
        public ?bool $preview = null,
        public ?string $externalId = null,
        public ?bool $eInvoice = null,
        public ?bool $downloadPdf = null,
    ) {}
}

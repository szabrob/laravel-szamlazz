<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Receipt;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class Receipt extends Data
{
    /**
     * @param  array<int, ReceiptItem>  $items
     * @param  array<int, ReceiptPayment>  $payments
     */
    public function __construct(
        public string $prefix,
        #[DataCollectionOf(ReceiptItem::class)]
        public array $items,
        public string $paymentMethod = 'készpénz',
        public string $currency = 'HUF',
        #[DataCollectionOf(ReceiptPayment::class)]
        public array $payments = [],
        public ?string $callId = null,
        public ?float $exchangeRate = null,
        public ?string $exchangeRateBank = null,
        public ?string $comment = null,
        public ?string $pdfTemplate = null,
        public ?string $ledgerBuyer = null,
        public ?string $orderNumber = null,
        public ?bool $downloadPdf = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Invoice;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

final class InvoicePayment extends Data
{
    public function __construct(
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d', type: CarbonImmutable::class)]
        public DateTimeInterface $date,
        public string $method,
        public float $amount,
        public ?string $description = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Invoice;

use Spatie\LaravelData\Data;

final class Seller extends Data
{
    public function __construct(
        public ?string $bank = null,
        public ?string $bankAccount = null,
        public ?string $emailReplyTo = null,
        public ?string $emailSubject = null,
        public ?string $emailBody = null,
        public ?string $signatoryName = null,
    ) {}
}

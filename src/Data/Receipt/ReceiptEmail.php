<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Receipt;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Data;

/**
 * Email an existing receipt to the buyer.
 */
final class ReceiptEmail extends Data
{
    public function __construct(
        public string $receiptNumber,
        #[Email]
        public string $email,
        public ?string $replyTo = null,
        public ?string $subject = null,
        public ?string $body = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Invoice;

use Spatie\LaravelData\Data;
use Szabrob\LaravelSzamlazz\Enums\TaxPayerType;

final class Buyer extends Data
{
    public function __construct(
        public string $name,
        public string $postCode,
        public string $city,
        public string $address,
        public ?string $country = null,
        public ?string $email = null,
        public ?bool $sendEmail = null,
        public ?TaxPayerType $taxPayerType = null,
        public ?string $taxNumber = null,
        public ?string $groupTaxNumber = null,
        public ?string $euTaxNumber = null,
        public ?string $postalName = null,
        public ?string $postalCountry = null,
        public ?string $postalPostCode = null,
        public ?string $postalCity = null,
        public ?string $postalAddress = null,
        public ?string $identifier = null,
        public ?string $signatoryName = null,
        public ?string $phone = null,
        public ?string $comment = null,
    ) {}
}

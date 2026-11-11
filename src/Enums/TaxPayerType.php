<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Enums;

/**
 * The buyer's VAT status (`vevo.adoalany`).
 */
enum TaxPayerType: int
{
    case NonEuEnterprise = 7;
    case EuEnterprise = 6;
    case HasHungarianTaxNumber = 1;
    case Unknown = 0;
    case NoTaxNumber = -1;
}

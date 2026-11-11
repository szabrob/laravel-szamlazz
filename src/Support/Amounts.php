<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Support;

/**
 * @internal Fills in missing line item totals from quantity, unit price and VAT rate.
 */
final class Amounts
{
    /**
     * @return array{net: float, vat: float, gross: float}
     */
    public static function resolve(
        float $quantity,
        float $netUnitPrice,
        string $vatRate,
        ?float $net = null,
        ?float $vat = null,
        ?float $gross = null,
    ): array {
        $net ??= round($quantity * $netUnitPrice, 2);
        $vat ??= is_numeric($vatRate) ? round($net * (float) $vatRate / 100, 2) : 0.0;
        $gross ??= round($net + $vat, 2);

        return ['net' => $net, 'vat' => $vat, 'gross' => $gross];
    }
}

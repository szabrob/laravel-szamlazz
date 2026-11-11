<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Support;

use InvalidArgumentException;

/**
 * @internal Fills in missing line item totals so that net + VAT = gross.
 *
 * Given amounts always win. The missing ones are derived from them first,
 * and only fall back to quantity × net unit price when nothing else is known:
 *
 * - only gross: net = gross / (1 + rate), VAT = gross − net
 * - gross and net: VAT = gross − net
 * - gross and VAT: net = gross − VAT
 * - otherwise: net = quantity × net unit price, VAT = net × rate, gross = net + VAT
 *
 * Non-numeric VAT keys (AAM, TAM, EU, ...) count as a zero rate. Three given
 * amounts that do not add up are rejected.
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
        ?string $item = null,
    ): array {
        $rate = is_numeric($vatRate) ? (float) $vatRate : 0.0;

        if ($gross !== null && $net === null && $vat === null) {
            $net = round($gross / (1 + $rate / 100), 2);
        }

        if ($net === null) {
            $net = $gross !== null && $vat !== null
                ? round($gross - $vat, 2)
                : round($quantity * $netUnitPrice, 2);
        }

        $vat ??= $gross !== null ? round($gross - $net, 2) : round($net * $rate / 100, 2);
        $gross ??= round($net + $vat, 2);

        if (round($net + $vat, 2) !== round($gross, 2)) {
            throw new InvalidArgumentException(sprintf(
                'Inconsistent amounts for line item%s: net %s + VAT %s does not equal gross %s.',
                $item !== null ? " [{$item}]" : '',
                self::format($net),
                self::format($vat),
                self::format($gross),
            ));
        }

        return ['net' => $net, 'vat' => $vat, 'gross' => $gross];
    }

    private static function format(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}

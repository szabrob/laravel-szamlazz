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
 * The VAT rate is a non-negative percentage with a dot decimal separator
 * ("27", "5", "0", "27.0") or one of the Számla Agent VAT keys in
 * VAT_KEYS, which count as a zero rate. Any other value (e.g. "27%" or
 * "27,0") is rejected, so a typo never silently becomes zero VAT. Three
 * given amounts that do not add up are rejected too.
 */
final class Amounts
{
    /**
     * Non-numeric Számla Agent VAT keys (`afakulcs`), all with zero VAT.
     *
     * @var list<string>
     */
    public const array VAT_KEYS = [
        'TAM', 'AAM', 'EU', 'EUK', 'MAA', 'F.AFA', 'K.AFA', 'ÁKK', 'TAHK', 'TEHK',
        'EUT', 'EUKT', 'KBAET', 'KBAUK', 'EAM', 'NAM', 'ATK', 'EUFAD37', 'EUFADE', 'EUE', 'HO',
    ];

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
        $rate = self::rate($vatRate, $item);

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

    private static function rate(string $vatRate, ?string $item): float
    {
        if (preg_match('/^\d+(\.\d+)?$/', $vatRate) === 1) {
            return (float) $vatRate;
        }

        if (in_array($vatRate, self::VAT_KEYS, true)) {
            return 0.0;
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid VAT rate "%s" for line item%s: use a percentage such as "27" or "5.5", or a Számla Agent VAT key (%s).',
            $vatRate,
            $item !== null ? " [{$item}]" : '',
            implode(', ', self::VAT_KEYS),
        ));
    }

    private static function format(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}

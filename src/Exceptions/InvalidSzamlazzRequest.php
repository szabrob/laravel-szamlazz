<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Exceptions;

use InvalidArgumentException;

/**
 * A request DTO that Számla Agent would reject. Thrown before anything is
 * sent, so no document is created. Extends InvalidArgumentException, so
 * existing `catch (InvalidArgumentException)` blocks keep working.
 */
class InvalidSzamlazzRequest extends InvalidArgumentException
{
    /**
     * @param  list<string>  $vatKeys
     */
    public static function invalidVatRate(string $vatRate, ?string $item, array $vatKeys): self
    {
        return new self(sprintf(
            'Invalid VAT rate "%s" for line item%s: use a percentage such as "27" or "5.5", or a Számla Agent VAT key (%s).',
            $vatRate,
            self::item($item),
            implode(', ', $vatKeys),
        ));
    }

    public static function inconsistentAmounts(?string $item, string $net, string $vat, string $gross): self
    {
        return new self(sprintf(
            'Inconsistent amounts for line item%s: net %s + VAT %s does not equal gross %s.',
            self::item($item),
            $net,
            $vat,
            $gross,
        ));
    }

    public static function missingItems(string $document): self
    {
        return new self("The {$document} needs at least one line item.");
    }

    public static function tooManyPayments(int $max, int $given): self
    {
        return new self("Számla Agent accepts at most {$max} payments per registration, {$given} given.");
    }

    public static function emptyInvoiceLookup(): self
    {
        return new self('An invoice lookup needs an invoice number, order number or external id.');
    }

    public static function proformaLookupWithoutNumber(): self
    {
        return new self('Deleting a proforma needs an invoice number or order number; an external id alone is not supported.');
    }

    public static function emptyReceiptLookup(): self
    {
        return new self('A receipt lookup needs a receipt number, order number or call id.');
    }

    private static function item(?string $item): string
    {
        return $item !== null ? " [{$item}]" : '';
    }
}

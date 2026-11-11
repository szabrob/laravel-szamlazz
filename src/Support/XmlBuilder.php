<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Support;

use BackedEnum;
use DateTimeInterface;
use DOMDocument;
use DOMElement;

/**
 * @internal Builds Számla Agent request XML from ordered element trees.
 *
 * Null values are skipped and lists produce one element per item, so an
 * empty list produces no element. An associative array whose values are all
 * null produces an empty element.
 */
final class XmlBuilder
{
    /**
     * @param  array<string, mixed>  $tree
     */
    public static function build(string $root, string $namespace, array $tree): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $element = $document->createElementNS($namespace, $root);
        $document->appendChild($element);

        self::appendChildren($document, $element, $namespace, $tree);

        return (string) $document->saveXML();
    }

    /**
     * @param  array<string, mixed>  $tree
     */
    private static function appendChildren(DOMDocument $document, DOMElement $parent, string $namespace, array $tree): void
    {
        foreach ($tree as $name => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value) && array_is_list($value)) {
                foreach ($value as $item) {
                    self::appendElement($document, $parent, $namespace, $name, $item);
                }

                continue;
            }

            self::appendElement($document, $parent, $namespace, $name, $value);
        }
    }

    private static function appendElement(DOMDocument $document, DOMElement $parent, string $namespace, string $name, mixed $value): void
    {
        $element = $document->createElementNS($namespace, $name);
        $parent->appendChild($element);

        if (is_array($value)) {
            /** @var array<string, mixed> $value */
            self::appendChildren($document, $element, $namespace, $value);

            return;
        }

        $element->appendChild($document->createTextNode(self::scalar($value)));
    }

    private static function scalar(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_float($value) => self::float($value),
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            $value instanceof BackedEnum => (string) $value->value,
            is_scalar($value) => (string) $value,
            default => '',
        };
    }

    private static function float(float $value): string
    {
        $formatted = rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
}

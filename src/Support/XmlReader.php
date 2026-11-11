<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Support;

use DOMDocument;
use DOMXPath;

/**
 * @internal Namespace-agnostic reader for Számla Agent response XML.
 */
final class XmlReader
{
    private function __construct(
        private readonly DOMXPath $xpath,
        public readonly string $xml,
    ) {}

    public static function tryParse(string $body): ?self
    {
        $trimmed = ltrim($body);

        if (! str_starts_with($trimmed, '<')) {
            return null;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($trimmed, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || $document->documentElement === null) {
            return null;
        }

        return new self(new DOMXPath($document), $trimmed);
    }

    /**
     * Read the text of an element by a slash separated path relative to the root element.
     */
    public function string(string $path): ?string
    {
        $segments = array_map(
            static fn (string $segment): string => "*[local-name()='{$segment}']",
            explode('/', $path),
        );

        $nodes = $this->xpath->query('/*/'.implode('/', $segments));

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim((string) $nodes->item(0)?->nodeValue);

        return $value === '' ? null : $value;
    }

    public function float(string $path): ?float
    {
        $value = $this->string($path);

        return $value !== null && is_numeric($value) ? (float) $value : null;
    }

    public function int(string $path): ?int
    {
        $value = $this->string($path);

        return $value !== null && is_numeric($value) ? (int) $value : null;
    }

    public function bool(string $path): ?bool
    {
        $value = $this->string($path);

        return $value === null ? null : strtolower($value) === 'true';
    }
}

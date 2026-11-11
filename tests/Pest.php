<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Szabrob\LaravelSzamlazz\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

const AGENT_URL = 'https://www.szamlazz.hu/szamla/';

/**
 * Return the XML document the package attached to a faked Számla Agent request.
 */
function sentXml(Request $request, string $field): string
{
    foreach ($request->data() as $part) {
        if (is_array($part) && ($part['name'] ?? null) === $field) {
            return (string) $part['contents'];
        }
    }

    throw new RuntimeException("The request has no [{$field}] multipart field.");
}

/**
 * Assert that the XML validates against an official Számla Agent XSD.
 */
function expectValidAgainstXsd(string $xml, string $schema): void
{
    $document = new DOMDocument;
    $document->loadXML($xml);

    libxml_use_internal_errors(true);
    $valid = $document->schemaValidate(__DIR__."/Fixtures/xsd/{$schema}.xsd");
    $errors = array_map(fn (LibXMLError $error): string => trim($error->message), libxml_get_errors());
    libxml_clear_errors();
    libxml_use_internal_errors(false);

    expect($valid)->toBeTrue(implode(PHP_EOL, $errors));
}

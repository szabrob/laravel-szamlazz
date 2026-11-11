<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Számla Agent credentials
    |--------------------------------------------------------------------------
    |
    | Use a Számla Agent key (recommended), or a username and password pair.
    | When an Agent key is set, the username and password are ignored.
    |
    */

    'api_key' => env('SZAMLAZZ_API_KEY'),

    'username' => env('SZAMLAZZ_USERNAME'),

    'password' => env('SZAMLAZZ_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    */

    'base_url' => env('SZAMLAZZ_BASE_URL', 'https://www.szamlazz.hu/szamla/'),

    'timeout' => env('SZAMLAZZ_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | e_invoice: create electronic (e-számla) documents instead of paper ones.
    | download_pdf: return the generated PDF with invoice and receipt results.
    | aggregator: optional aggregator identifier agreed with Számlázz.hu.
    |
    | e_invoice and download_pdf can be overridden on each request DTO.
    |
    */

    'e_invoice' => env('SZAMLAZZ_E_INVOICE', true),

    'download_pdf' => env('SZAMLAZZ_DOWNLOAD_PDF', true),

    'aggregator' => env('SZAMLAZZ_AGGREGATOR'),

];

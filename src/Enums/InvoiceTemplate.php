<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Enums;

enum InvoiceTemplate: string
{
    case Default = 'SzlaMost';
    case Basic = 'SzlaAlap';
    case NoEnvelope = 'SzlaNoEnv';
    case Thermal8cm = 'Szla8cm';
    case Retro = 'SzlaTomb';
    case Waybill = 'SzlaFuvarlevelesAlap';
}

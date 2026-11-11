<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Enums;

enum InvoiceType: string
{
    /** Normál számla. */
    case Invoice = 'invoice';

    /** Díjbekérő. */
    case Proforma = 'proforma';

    /** Előlegszámla. */
    case Prepayment = 'prepayment';

    /** Végszámla. */
    case Final = 'final';

    /** Helyesbítő számla. */
    case Corrective = 'corrective';

    /** Szállítólevél. */
    case DeliveryNote = 'delivery_note';
}

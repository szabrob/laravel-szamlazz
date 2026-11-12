<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data\Results\Concerns;

/**
 * For result DTOs with a `?string $pdf` property holding raw PDF bytes.
 */
trait HasPdf
{
    public function hasPdf(): bool
    {
        return $this->pdf !== null && $this->pdf !== '';
    }
}

<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Exceptions;

use RuntimeException;

class SzamlazzException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $errorCode = null,
        public readonly ?int $httpStatus = null,
    ) {
        parent::__construct($message, $errorCode ?? 0);
    }

    public static function fromApi(?int $errorCode, ?string $message, ?int $httpStatus = null): self
    {
        $message = $message !== null && $message !== '' ? $message : 'Unknown Számla Agent error.';

        return new self(
            $errorCode !== null ? "Számla Agent error [{$errorCode}]: {$message}" : "Számla Agent error: {$message}",
            $errorCode,
            $httpStatus,
        );
    }

    public static function maintenance(): self
    {
        return new self('The Számla Agent service is temporarily unavailable (maintenance).');
    }

    public static function missingCredentials(): self
    {
        return new self('Számla Agent credentials are missing. Set SZAMLAZZ_API_KEY, or SZAMLAZZ_USERNAME and SZAMLAZZ_PASSWORD.');
    }

    public static function unexpectedResponse(string $reason, ?int $httpStatus = null): self
    {
        return new self("Unexpected Számla Agent response: {$reason}", null, $httpStatus);
    }
}

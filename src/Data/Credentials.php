<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data;

use Spatie\LaravelData\Data;

/**
 * Számla Agent authentication: an Agent key, or a username and password pair.
 */
final class Credentials extends Data
{
    public function __construct(
        public ?string $apiKey = null,
        public ?string $username = null,
        public ?string $password = null,
    ) {}

    public static function fromApiKey(string $apiKey): self
    {
        return new self(apiKey: $apiKey);
    }

    public static function fromLogin(string $username, string $password): self
    {
        return new self(username: $username, password: $password);
    }

    public function isComplete(): bool
    {
        return filled($this->apiKey) || (filled($this->username) && filled($this->password));
    }
}

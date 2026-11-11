<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Data;

use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

/**
 * Számla Agent authentication: an Agent key, or a username and password pair.
 *
 * The Agent key and password are hidden from toArray() and toJson() output.
 */
final class Credentials extends Data
{
    public function __construct(
        #[Hidden]
        public ?string $apiKey = null,
        public ?string $username = null,
        #[Hidden]
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

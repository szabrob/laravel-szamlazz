<?php

declare(strict_types=1);

namespace LaravelSzamlazz\LaravelSzamlazz\Console\Commands;

use Illuminate\Console\Command;

class LaravelSzamlazzCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'laravel-szamlazz:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package laravel-szamlazz.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('LaravelSzamlazz placeholder command executed.');

        return self::SUCCESS;
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeployTest extends TestCase
{
    /** Wasmer Edge runs plain `php artisan migrate` after deploy and can't answer prompts. */
    public function test_migrate_creates_the_tables_in_production_without_asking(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('migrate')->assertSuccessful();

        $this->assertTrue(Schema::hasTable('participants'));
        $this->assertTrue(Schema::hasTable('sessions'));
    }

    public function test_destructive_migrate_commands_still_ask_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('migrate:fresh')
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertFailed();
    }
}

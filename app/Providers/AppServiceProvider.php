<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerUnattendedMigrate();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Wasmer Edge runs `php artisan migrate` on every deploy without --force. In
     * production Laravel then asks "Are you sure?", nobody can answer, and the
     * tables are never created. Plain `migrate` only adds missing tables, so let
     * it run without asking. Destructive commands (migrate:fresh, reset, …) still ask.
     */
    private function registerUnattendedMigrate(): void
    {
        $this->app->extend(MigrateCommand::class, fn (MigrateCommand $command, $app) => new class($app['migrator'], $app[Dispatcher::class]) extends MigrateCommand
        {
            public function confirmToProceed($warning = 'Application In Production', $callback = null): bool
            {
                return true;
            }
        });
    }
}

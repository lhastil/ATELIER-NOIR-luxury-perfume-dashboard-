<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run against anything but a dedicated test database.
     *
     * Runs before RefreshDatabase migrates, so a cached config or a wrong
     * environment can never wipe development data.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $database = $app['config']->get('database.connections.'.$app['config']->get('database.default').'.database');

        if ($database !== ':memory:' && ! str_ends_with((string) $database, '_testing')) {
            throw new RuntimeException(
                "Tests must use an in-memory or *_testing database, not [{$database}]. "
                .'Run `php artisan config:clear`, then `composer test` or `composer test:mysql`.'
            );
        }

        return $app;
    }
}

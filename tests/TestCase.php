<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Views must render without a compiled front-end build (fresh clones, CI).
        $this->withoutVite();

        // ->mainContent() narrows a page's assertions to its <main> element. The
        // shared top bar lists recent orders, customers and messages (the
        // notification bell), which would otherwise match assertDontSee() checks.
        TestResponse::macro('mainContent', function () {
            /** @var TestResponse $this */
            $found = preg_match('#<main\b[^>]*>(.*)</main>#s', $this->getContent(), $match);
            Assert::assertSame(1, $found, 'The response has no <main> element.');

            return TestResponse::fromBaseResponse(new Response($match[1]));
        });
    }

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

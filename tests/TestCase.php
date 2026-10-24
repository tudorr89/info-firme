<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guardAgainstRunningOnADevelopmentDatabase();
    }

    /**
     * Tests truncate and migrate the database they run against, so they are
     * only allowed to touch a dedicated database. Without this guard a
     * misconfigured environment (or a missing DB_DATABASE in phpunit.xml)
     * would wipe the development data.
     */
    private function guardAgainstRunningOnADevelopmentDatabase(): void
    {
        if (app()->environment() !== 'testing') {
            return;
        }

        $database = DB::connection()->getDatabaseName();

        if ($database === ':memory:' || str_ends_with($database, '_testing')) {
            return;
        }

        $this->fail(sprintf(
            'Refusing to run tests against the `%s` database: tests destroy its data. '
            .'Point DB_DATABASE at a database whose name ends in `_testing` (see phpunit.xml).',
            $database
        ));
    }
}

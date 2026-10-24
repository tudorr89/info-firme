<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTruncation;

/**
 * Committed-write test isolation for the tests that exercise the full-text
 * company search.
 *
 * `RefreshDatabase` wraps every test in a transaction and rolls it back, which
 * is the preferred default because it never destroys anything. InnoDB however
 * does not expose rows written inside an uncommitted transaction to FULLTEXT
 * indexes, so a `MATCH ... AGAINST` query cannot see fixtures that were only
 * inserted in the current transaction. These tests therefore commit their
 * fixtures and truncate the tables instead.
 *
 * The tables are truncated again in `tearDown()` so nothing leaks into the
 * tests that rely on `RefreshDatabase`'s rollback.
 */
trait TruncatesCommittedDatabase
{
    use DatabaseTruncation;

    /**
     * The `settings` row is created by a migration, not by the tests, so it has
     * to survive the truncation.
     *
     * @var array<int, string>
     */
    protected array $exceptTables = ['settings'];

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }
}

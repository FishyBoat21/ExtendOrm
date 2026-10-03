<?php
namespace FishyBoat21\ExtendOrm\Dialect;

/**
 * ANSI-style quoting (double quotes), used by SQLite, PostgreSQL and SQL Server.
 *
 * The three share the behaviour implemented here today; if one of them ever needs
 * driver-specific rendering it can subclass this rather than diverge silently.
 */
final class AnsiDialect extends AbstractDialect
{
    protected function quoteCharacter(): string
    {
        return '"';
    }
}

<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Dialect\AnsiDialect;
use FishyBoat21\ExtendOrm\Dialect\DialectFactory;
use FishyBoat21\ExtendOrm\Dialect\MySqlDialect;
use FishyBoat21\ExtendOrm\ExtendORMException;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilder2;

final class DialectTest extends TestCase
{
    public function testFactoryResolvesMySql(): void
    {
        $dialect = DialectFactory::forDriver('mysql');

        $this->assertInstanceOf(MySqlDialect::class, $dialect);
        $this->assertSame('mysql', $dialect->name());
    }

    public function testFactoryMatchesTheDriverNameCaseInsensitively(): void
    {
        $this->assertInstanceOf(MySqlDialect::class, DialectFactory::forDriver('MySQL'));
    }

    public function testFactoryFallsBackToAnsiForOtherDrivers(): void
    {
        $this->assertInstanceOf(AnsiDialect::class, DialectFactory::forDriver('sqlite'));
        $this->assertInstanceOf(AnsiDialect::class, DialectFactory::forDriver('pgsql'));
    }

    public function testMySqlQuotesWithBackticks(): void
    {
        $this->assertSame('`users`', (new MySqlDialect())->quoteIdentifier('users'));
    }

    public function testAnsiQuotesWithDoubleQuotes(): void
    {
        $this->assertSame('"users"', (new AnsiDialect('sqlite'))->quoteIdentifier('users'));
    }

    public function testQualifiedAndStarIdentifiers(): void
    {
        $dialect = new MySqlDialect();

        $this->assertSame('`users`.`id`', $dialect->quoteIdentifier('users.id'));
        $this->assertSame('*', $dialect->quoteIdentifier('*'));
        $this->assertSame('`users`.*', $dialect->quoteIdentifier('users.*'));
    }

    public function testEmbeddedQuoteCharacterIsEscaped(): void
    {
        $this->assertSame('`we``ird`', (new MySqlDialect())->quoteIdentifier('we`ird'));
        $this->assertSame('"we""ird"', (new AnsiDialect('sqlite'))->quoteIdentifier('we"ird'));
    }

    public function testEmptyIdentifierThrows(): void
    {
        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('empty identifier');

        (new MySqlDialect())->quoteIdentifier('  ');
    }

    public function testMalformedQualifiedIdentifierThrows(): void
    {
        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('Malformed identifier');

        (new MySqlDialect())->quoteIdentifier('users.');
    }

    public function testLimitClause(): void
    {
        $dialect = new AnsiDialect('sqlite');

        $this->assertSame('', $dialect->limitClause(null, null));
        $this->assertSame(' LIMIT 10', $dialect->limitClause(10, null));
        $this->assertSame(' LIMIT 10', $dialect->limitClause(10, 0));
        $this->assertSame(' LIMIT 10 OFFSET 20', $dialect->limitClause(10, 20));
    }

    public function testBuilderPicksUpTheConnectionDialect(): void
    {
        // The suite runs on SQLite, so the builder must resolve the ANSI dialect.
        $this->assertSame('sqlite', $this->queryBuilder()->dialect()->name());
    }

    public function testBuilderAcceptsAnExplicitDialect(): void
    {
        $builder = new QueryBuilder2($this->pdo, new MySqlDialect());

        $this->assertSame('mysql', $builder->dialect()->name());
    }
}

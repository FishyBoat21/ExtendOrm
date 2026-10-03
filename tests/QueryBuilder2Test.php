<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\ExtendORMException;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderSortType;
use PDOException;

final class QueryBuilder2Test extends TestCase
{
    public function testSelectWhereReturnsMatchingRows(): void
    {
        $this->createUser('alice');
        $this->createUser('bob');

        $rows = $this->queryBuilder()
            ->select('id, username')
            ->from('users')
            ->where('username', QueryBuilderOperator::Equals, 'alice')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('alice', $rows[0]['username']);
    }

    /**
     * "order" is a SQL reserved word; unquoted it would produce a syntax error.
     */
    public function testReservedWordIdentifierIsQuoted(): void
    {
        $user = $this->createUser('alice');
        $this->createPost($user, 'first', 7);

        $rows = $this->queryBuilder()
            ->select('id, order')
            ->from('posts')
            ->where('order', QueryBuilderOperator::Equals, 7)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame(7, (int) $rows[0]['order']);
    }

    public function testPagingAppliesLimitAndOffset(): void
    {
        foreach (['a', 'b', 'c', 'd'] as $name) {
            $this->createUser($name);
        }

        $rows = $this->queryBuilder()
            ->select('id')
            ->from('users')
            ->sort('id')
            ->page(2, 1)
            ->get();

        $this->assertCount(2, $rows);
        $this->assertSame(2, (int) $rows[0]['id']);
        $this->assertSame(3, (int) $rows[1]['id']);
    }

    public function testSortDescending(): void
    {
        foreach (['a', 'b', 'c'] as $name) {
            $this->createUser($name);
        }

        $rows = $this->queryBuilder()
            ->select('id')
            ->from('users')
            ->sort('id', QueryBuilderSortType::Descending)
            ->get();

        $this->assertSame(3, (int) $rows[0]['id']);
    }

    public function testIsOperatorAcceptsNull(): void
    {
        $this->createUser('alice', 'alice@example.com');
        $this->createUser('bob');

        $rows = $this->queryBuilder()
            ->select('username')
            ->from('users')
            ->where('email', QueryBuilderOperator::Is, null)
            ->get();

        $this->assertSame(['bob'], array_column($rows, 'username'));
    }

    public function testIsNullOperator(): void
    {
        $this->createUser('alice', 'alice@example.com');
        $this->createUser('bob');

        $rows = $this->queryBuilder()
            ->select('username')
            ->from('users')
            ->where('email', QueryBuilderOperator::IsNull, null)
            ->get();

        $this->assertSame(['bob'], array_column($rows, 'username'));
    }

    public function testIsNotNullOperator(): void
    {
        $this->createUser('alice', 'alice@example.com');
        $this->createUser('bob');

        $rows = $this->queryBuilder()
            ->select('username')
            ->from('users')
            ->where('email', QueryBuilderOperator::IsNotNull, null)
            ->get();

        $this->assertSame(['alice'], array_column($rows, 'username'));
    }

    public function testIsOperatorRejectsNonNullValue(): void
    {
        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('only supports NULL');

        $this->queryBuilder()
            ->select('id')
            ->from('users')
            ->where('email', QueryBuilderOperator::Is, 'a@example.com');
    }

    public function testEmptyIdentifierIsRejected(): void
    {
        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('empty identifier');

        $this->queryBuilder()->select('id')->from('')->get();
    }

    public function testInsertReturnsLastInsertId(): void
    {
        $id = $this->queryBuilder()->insert('users', [
            'username' => 'alice',
            'email' => 'alice@example.com',
            'created_at' => null,
        ]);

        $this->assertSame(1, $id);
    }

    public function testUpdateAndDeleteReportAffectedRows(): void
    {
        $this->createUser('alice');
        $qb = $this->queryBuilder();

        $updated = $qb->update('users', ['username' => 'alicia'])
            ->where('id', QueryBuilderOperator::Equals, 1)
            ->exec();
        $this->assertSame(1, $updated);

        $deleted = $qb->delete('users')
            ->where('id', QueryBuilderOperator::Equals, 1)
            ->exec();
        $this->assertSame(1, $deleted);
    }

    /**
     * The SQL text must never reach the exception message, which callers may
     * render to end users.
     */
    public function testQueryFailureDoesNotLeakSqlInTheMessage(): void
    {
        try {
            $this->queryBuilder()->select('id')->from('no_such_table')->get();
            $this->fail('Expected a PDOException.');
        } catch (PDOException $e) {
            $this->assertStringNotContainsString('| SQL:', $e->getMessage());
            $this->assertStringNotContainsString('SELECT "id" FROM', $e->getMessage());
        }
    }
}

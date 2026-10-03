<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Database;
use PDO;
use RuntimeException;

final class TransactionTest extends TestCase
{
    public function testCommitPersistsChanges(): void
    {
        Database::GetInstance()->Transaction(function (): void {
            $this->createUser('alice');
        });

        $this->assertSame(1, $this->userCount());
    }

    public function testTransactionReturnsTheClosureResult(): void
    {
        $result = Database::GetInstance()->Transaction(static fn (): string => 'done');

        $this->assertSame('done', $result);
    }

    public function testRollbackDiscardsChangesOnException(): void
    {
        try {
            Database::GetInstance()->Transaction(function (): void {
                $this->createUser('alice');
                throw new RuntimeException('boom');
            });
            $this->fail('Expected the exception to bubble out.');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame(0, $this->userCount());
    }

    /**
     * A nested Transaction() must behave like a savepoint: rolling it back keeps
     * the work the outer transaction already did.
     */
    public function testNestedTransactionRollsBackOnlyTheInnerWork(): void
    {
        $db = Database::GetInstance();

        $db->Transaction(function () use ($db): void {
            $this->createUser('outer-before');

            try {
                $db->Transaction(function (): void {
                    $this->createUser('inner');
                    throw new RuntimeException('inner boom');
                });
                $this->fail('Expected the inner exception to bubble out.');
            } catch (RuntimeException) {
                // Swallowed so the outer transaction can continue.
            }

            $this->createUser('outer-after');
        });

        $names = $this->pdo->query('SELECT username FROM users ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

        $this->assertSame(['outer-before', 'outer-after'], $names);
    }

    public function testNestedTransactionCommitsWhenTheInnerClosureSucceeds(): void
    {
        $db = Database::GetInstance();

        $db->Transaction(function () use ($db): void {
            $this->createUser('outer');
            $db->Transaction(function (): void {
                $this->createUser('inner');
            });
        });

        $this->assertSame(2, $this->userCount());
    }

}

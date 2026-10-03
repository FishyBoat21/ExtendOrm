<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\Criterion;
use FishyBoat21\ExtendOrm\ExtendORMException;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderSortType;
use FishyBoat21\ExtendOrm\Sort;
use FishyBoat21\ExtendOrm\Tests\Models\User;

final class ModelCrudTest extends TestCase
{
    public function testSaveInsertsAndPopulatesThePrimaryKey(): void
    {
        $user = new User($this->queryBuilder());
        $user->username = 'alice';
        $user->email = 'alice@example.com';
        $user->save();

        $this->assertSame(1, $user->id);
        $this->assertSame(
            'alice',
            $this->pdo->query('SELECT username FROM users WHERE id = 1')->fetchColumn()
        );
    }

    public function testSaveUpdatesWhenThePrimaryKeyIsSet(): void
    {
        $user = $this->createUser('alice');

        $user->username = 'alicia';
        $user->save();

        $this->assertSame(1, $this->userCount());
        $this->assertSame(
            'alicia',
            $this->pdo->query('SELECT username FROM users WHERE id = 1')->fetchColumn()
        );
    }

    /**
     * A property whose name differs from its column name must still persist.
     */
    public function testSavePersistsPropertyWhoseNameDiffersFromColumn(): void
    {
        $user = $this->createUser('alice');

        $user->createdAt = '2026-10-02 12:00:00';
        $user->save();

        $this->assertSame(
            '2026-10-02 12:00:00',
            $this->pdo->query('SELECT created_at FROM users WHERE id = 1')->fetchColumn()
        );
    }

    public function testDeleteRemovesTheRow(): void
    {
        $user = $this->createUser('alice');
        $user->delete();

        $this->assertSame(0, $this->userCount());
    }

    public function testDeleteOnUnsavedRecordThrows(): void
    {
        $user = new User($this->queryBuilder());

        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('Not a valid record');

        $user->delete();
    }

    public function testFindOneReturnsNullWhenNothingMatches(): void
    {
        $criteria = (new Criteria())->Add(new Criterion('username', QueryBuilderOperator::Equals, 'nobody'));

        $this->assertNull(User::FindOne($criteria, $this->queryBuilder()));
    }

    public function testFindManyTranslatesAPropertyNameToItsColumn(): void
    {
        $alice = $this->createUser('alice');
        $alice->createdAt = '2020-01-01 00:00:00';
        $alice->save();
        $this->createUser('bob');

        $criteria = (new Criteria())->Add(new Criterion('createdAt', QueryBuilderOperator::Equals, '2020-01-01 00:00:00'));
        $found = User::FindMany($criteria, $this->queryBuilder());

        $this->assertCount(1, $found);
        $this->assertSame('alice', $found[0]->username);
    }

    /**
     * Criteria written against the raw schema column must keep working.
     */
    public function testFindManyAlsoAcceptsTheRawColumnName(): void
    {
        $alice = $this->createUser('alice');
        $alice->createdAt = '2020-01-01 00:00:00';
        $alice->save();
        $this->createUser('bob');

        $criteria = (new Criteria())->Add(new Criterion('created_at', QueryBuilderOperator::Equals, '2020-01-01 00:00:00'));

        $this->assertCount(1, User::FindMany($criteria, $this->queryBuilder()));
    }

    /**
     * Before the guard was added, an unknown key became an empty column name and
     * produced malformed SQL instead of a clear error.
     */
    public function testUnknownCriterionKeyThrowsInsteadOfBuildingBrokenSql(): void
    {
        $criteria = (new Criteria())->Add(new Criterion('nope', QueryBuilderOperator::Equals, 1));

        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage("Unknown property or column 'nope'");

        User::FindMany($criteria, $this->queryBuilder());
    }

    public function testUnknownSortKeyThrows(): void
    {
        $criteria = (new Criteria())->AddSort(new Sort('nope'));

        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage("Unknown property or column 'nope'");

        User::FindMany($criteria, $this->queryBuilder());
    }

    public function testPagingLimitsAndOffsets(): void
    {
        foreach (['a', 'b', 'c', 'd'] as $name) {
            $this->createUser($name);
        }

        $criteria = (new Criteria())->AddSort(new Sort('id'));
        $page = User::Paging(2, 1, $criteria, $this->queryBuilder());

        $this->assertCount(2, $page);
        $this->assertSame('b', $page[0]->username);
        $this->assertSame('c', $page[1]->username);
    }

    public function testFindOneReturnsTheFirstRowAccordingToSort(): void
    {
        foreach (['a', 'b', 'c'] as $name) {
            $this->createUser($name);
        }

        $criteria = (new Criteria())->AddSort(new Sort('id', QueryBuilderSortType::Descending));
        $user = User::FindOne($criteria, $this->queryBuilder());

        $this->assertNotNull($user);
        $this->assertSame('c', $user->username);
    }

    public function testSaveIsChainable(): void
    {
        $user = new User($this->queryBuilder());
        $user->username = 'alice';

        $this->assertSame($user, $user->save());
    }
}

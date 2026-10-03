<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\Criterion;
use FishyBoat21\ExtendOrm\ExtendORMException;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;
use FishyBoat21\ExtendOrm\Sort;
use FishyBoat21\ExtendOrm\Tests\Models\User;

final class CriteriaOperatorsTest extends TestCase
{
    /** @param list<User> $users */
    private static function names(array $users): array
    {
        return array_map(static fn (User $user): string => $user->username, $users);
    }

    public function testInMatchesAnyListedValue(): void
    {
        foreach (['alice', 'bob', 'carol'] as $name) {
            $this->createUser($name);
        }

        $criteria = (new Criteria())
            ->Add(new Criterion('username', QueryBuilderOperator::In, ['alice', 'carol']))
            ->AddSort(new Sort('id'));

        $this->assertSame(['alice', 'carol'], self::names(User::FindMany($criteria, $this->queryBuilder())));
    }

    public function testNotInExcludesListedValues(): void
    {
        foreach (['alice', 'bob', 'carol'] as $name) {
            $this->createUser($name);
        }

        $criteria = (new Criteria())
            ->Add(new Criterion('username', QueryBuilderOperator::NotIn, ['alice', 'carol']))
            ->AddSort(new Sort('id'));

        $this->assertSame(['bob'], self::names(User::FindMany($criteria, $this->queryBuilder())));
    }

    public function testInRejectsAnEmptyList(): void
    {
        $criteria = (new Criteria())->Add(new Criterion('username', QueryBuilderOperator::In, []));

        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('non-empty array');

        User::FindMany($criteria, $this->queryBuilder());
    }

    public function testBetweenMatchesAnInclusiveRange(): void
    {
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            $this->createUser($name);
        }

        $criteria = (new Criteria())
            ->Add(new Criterion('id', QueryBuilderOperator::Between, [2, 4]))
            ->AddSort(new Sort('id'));

        $this->assertSame(['b', 'c', 'd'], self::names(User::FindMany($criteria, $this->queryBuilder())));
    }

    public function testNotBetweenExcludesTheRange(): void
    {
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            $this->createUser($name);
        }

        $criteria = (new Criteria())
            ->Add(new Criterion('id', QueryBuilderOperator::NotBetween, [2, 4]))
            ->AddSort(new Sort('id'));

        $this->assertSame(['a', 'e'], self::names(User::FindMany($criteria, $this->queryBuilder())));
    }

    public function testBetweenRequiresTwoBounds(): void
    {
        $criteria = (new Criteria())->Add(new Criterion('id', QueryBuilderOperator::Between, [1]));

        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('two-element array');

        User::FindMany($criteria, $this->queryBuilder());
    }

    public function testOrConditionWidensTheResultSet(): void
    {
        foreach (['alice', 'bob', 'carol'] as $name) {
            $this->createUser($name);
        }

        $criteria = new Criteria();
        $criteria->Add(new Criterion('username', QueryBuilderOperator::Equals, 'alice'));
        $criteria->AddOr(new Criterion('username', QueryBuilderOperator::Equals, 'carol'));
        $criteria->AddSort(new Sort('id'));

        $this->assertSame(['alice', 'carol'], self::names(User::FindMany($criteria, $this->queryBuilder())));
    }

    /**
     * SQL precedence applies: a AND b OR c groups as (a AND b) OR c.
     */
    public function testAndOrPrecedenceFollowsSql(): void
    {
        foreach (['alice', 'bob', 'carol'] as $name) {
            $this->createUser($name);
        }

        $criteria = new Criteria();
        $criteria->Add(new Criterion('username', QueryBuilderOperator::NotEqual, 'nobody'));
        $criteria->Add(new Criterion('username', QueryBuilderOperator::Equals, 'bob'));
        $criteria->AddOr(new Criterion('username', QueryBuilderOperator::Equals, 'carol'));

        $names = self::names(User::FindMany($criteria, $this->queryBuilder()));
        sort($names);

        $this->assertSame(['bob', 'carol'], $names);
    }

    /**
     * A leading OR must still compile to a statement with no dangling connector.
     */
    public function testLeadingOrConditionCompiles(): void
    {
        $this->createUser('alice');
        $this->createUser('bob');

        $criteria = new Criteria();
        $criteria->AddOr(new Criterion('username', QueryBuilderOperator::Equals, 'alice'));

        $this->assertSame(['alice'], self::names(User::FindMany($criteria, $this->queryBuilder())));
    }

    public function testInWorksThroughAPropertyNameThatDiffersFromItsColumn(): void
    {
        $alice = $this->createUser('alice');
        $alice->createdAt = '2020-01-01 00:00:00';
        $alice->save();
        $bob = $this->createUser('bob');
        $bob->createdAt = '2021-01-01 00:00:00';
        $bob->save();

        $criteria = (new Criteria())
            ->Add(new Criterion('createdAt', QueryBuilderOperator::In, ['2021-01-01 00:00:00']));

        $this->assertSame(['bob'], self::names(User::FindMany($criteria, $this->queryBuilder())));
    }
}

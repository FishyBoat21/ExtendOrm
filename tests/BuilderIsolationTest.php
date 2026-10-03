<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;
use FishyBoat21\ExtendOrm\Tests\Models\User;

/**
 * A builder holds the statement being assembled, so a model query must never
 * mutate the builder the caller handed it.
 */
final class BuilderIsolationTest extends TestCase
{
    public function testAModelQueryDoesNotResetTheCallersBuilder(): void
    {
        $qb = $this->queryBuilder();
        $qb->select('id, username')->from('users');

        // Reuse the same builder for a model query before finishing the chain.
        User::FindMany(new Criteria(), $qb);

        $this->createUser('alice');
        $rows = $qb->where('username', QueryBuilderOperator::Equals, 'alice')->get();

        $this->assertSame(['alice'], array_column($rows, 'username'));
    }

    public function testARelationLoadDoesNotResetTheCallersBuilder(): void
    {
        $user = $this->createUser('alice');
        $this->createPost($user, 'one', 1);

        $qb = $this->queryBuilder();
        $qb->select('id, username')->from('users');

        // Triggers a relation query through the model's own builder.
        $this->assertCount(1, $user->posts);

        $this->assertCount(1, $qb->get());
    }

    public function testNewQueryStartsFromACleanState(): void
    {
        $qb = $this->queryBuilder();
        $qb->select('id')->from('users')->where('id', QueryBuilderOperator::Equals, 99);

        $this->createUser('alice');

        $fresh = $qb->newQuery();
        $this->assertCount(1, $fresh->select('id')->from('users')->get());

        // The original chain is untouched and still filters on id = 99.
        $this->assertSame([], $qb->get());
    }

    public function testNewQueryKeepsTheDialect(): void
    {
        $qb = $this->queryBuilder();

        $this->assertSame($qb->dialect()->name(), $qb->newQuery()->dialect()->name());
    }
}

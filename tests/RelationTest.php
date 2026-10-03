<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\Criterion;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;
use FishyBoat21\ExtendOrm\Tests\Models\Post;
use FishyBoat21\ExtendOrm\Tests\Models\Profile;
use FishyBoat21\ExtendOrm\Tests\Models\User;
use FishyBoat21\ExtendOrm\Tests\Support\QuerySpy;

final class RelationTest extends TestCase
{
    public function testHasManyReturnsEveryRelatedRow(): void
    {
        $user = $this->createUser('alice');
        $this->createPost($user, 'first', 1);
        $this->createPost($user, 'second', 2);

        $posts = $user->posts;

        $this->assertCount(2, $posts);
        $this->assertContainsOnlyInstancesOf(Post::class, $posts);
    }

    public function testHasManyReturnsAnEmptyArrayWhenThereAreNoRelatedRows(): void
    {
        $user = $this->createUser('alice');

        $this->assertSame([], $user->posts);
    }

    public function testHasOneResolvesTheRelatedRow(): void
    {
        $user = $this->createUser('alice');

        $profile = new Profile($this->queryBuilder());
        $profile->userId = $user->id;
        $profile->phone = '+1-555-0123';
        $profile->save();

        $this->assertInstanceOf(Profile::class, $user->profile);
        $this->assertSame('+1-555-0123', $user->profile->phone);
    }

    public function testHasOneReturnsNullWhenThereIsNoRelatedRow(): void
    {
        $user = $this->createUser('alice');

        $this->assertNull($user->profile);
    }

    /**
     * Post declares foreignKey 'user_id' (a column) while its property is
     * $userId, so this covers the column -> property translation.
     */
    public function testBelongsToResolvesTheOwnerWhenPropertyDiffersFromColumn(): void
    {
        $user = $this->createUser('alice');
        $post = $this->createPost($user, 'hello');

        $author = $post->author;

        $this->assertInstanceOf(User::class, $author);
        $this->assertSame('alice', $author->username);
    }

    public function testRelationIsMemoizedPerInstance(): void
    {
        $user = $this->createUser('alice');
        $this->createPost($user, 'first', 1);

        $spy = new QuerySpy($this->queryBuilder());
        $criteria = (new Criteria())->Add(new Criterion('id', QueryBuilderOperator::Equals, $user->id));

        $loaded = User::FindOne($criteria, $spy);
        $this->assertNotNull($loaded);
        $this->assertSame(1, $spy->selects(), 'Loading the user costs one SELECT.');

        $first = $loaded->posts;
        $second = $loaded->posts;
        $accessedAgain = $loaded->posts;

        $this->assertSame($first, $second);
        $this->assertSame($first, $accessedAgain);
        $this->assertSame(2, $spy->selects(), 'Repeated access must not re-query.');
    }

}

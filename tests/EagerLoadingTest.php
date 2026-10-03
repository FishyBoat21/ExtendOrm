<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\Criterion;
use FishyBoat21\ExtendOrm\ExtendORMException;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;
use FishyBoat21\ExtendOrm\Sort;
use FishyBoat21\ExtendOrm\Tests\Models\Post;
use FishyBoat21\ExtendOrm\Tests\Models\Profile;
use FishyBoat21\ExtendOrm\Tests\Models\User;
use FishyBoat21\ExtendOrm\Tests\Support\QuerySpy;

final class EagerLoadingTest extends TestCase
{
    /**
     * Without eager loading, three users cost one query plus one per user when
     * the relation is touched. With it, the relations cost a single query.
     */
    public function testHasManyIsLoadedInASingleQueryForTheWholeSet(): void
    {
        $users = [];
        foreach (['a', 'b', 'c'] as $name) {
            $users[] = $this->createUser($name);
        }
        foreach ($users as $user) {
            $this->createPost($user, 'post-' . $user->id, 1);
        }

        $spy = new QuerySpy($this->queryBuilder());
        $criteria = (new Criteria())->AddWith('posts')->AddSort(new Sort('id'));

        $loaded = User::FindMany($criteria, $spy);
        $this->assertCount(3, $loaded);
        $this->assertSame(2, $spy->selects(), 'One SELECT for the users plus one for all their posts.');

        foreach ($loaded as $user) {
            $this->assertCount(1, $user->posts);
        }
        $this->assertSame(2, $spy->selects(), 'Touching a preloaded relation must not query again.');
    }

    public function testBelongsToIsEagerLoaded(): void
    {
        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $this->createPost($alice, 'one');
        $this->createPost($bob, 'two');

        $spy = new QuerySpy($this->queryBuilder());
        $criteria = (new Criteria())->AddWith('author')->AddSort(new Sort('id'));

        $posts = Post::FindMany($criteria, $spy);
        $this->assertSame(2, $spy->selects());

        // The author's property name differs from its column, so this also proves
        // the key translation during preloading.
        $this->assertSame('alice', $posts[0]->author->username);
        $this->assertSame('bob', $posts[1]->author->username);
        $this->assertSame(2, $spy->selects());
    }

    public function testHasOneIsEagerLoaded(): void
    {
        $user = $this->createUser('alice');
        $profile = new Profile($this->queryBuilder());
        $profile->userId = $user->id;
        $profile->phone = '+1-555-0123';
        $profile->save();

        $spy = new QuerySpy($this->queryBuilder());
        $loaded = User::FindOne((new Criteria())->AddWith('profile'), $spy);

        $this->assertNotNull($loaded);
        $this->assertInstanceOf(Profile::class, $loaded->profile);
        $this->assertSame('+1-555-0123', $loaded->profile->phone);
        $this->assertSame(2, $spy->selects());
    }

    public function testRelationWithNoMatchesResolvesToAnEmptyValue(): void
    {
        $withPosts = $this->createUser('alice');
        $this->createPost($withPosts, 'one', 1);
        $this->createUser('bob');

        $criteria = (new Criteria())->AddWith('posts')->AddSort(new Sort('id'));
        $loaded = User::FindMany($criteria, $this->queryBuilder());

        $this->assertCount(1, $loaded[0]->posts);
        $this->assertSame([], $loaded[1]->posts, 'HasMany with no rows is an empty array.');

        $profiles = (new Criteria())->AddWith('profile')->AddSort(new Sort('id'));
        $this->assertNull(User::FindMany($profiles, $this->queryBuilder())[0]->profile);
    }

    public function testEagerLoadingAnEmptyResultSetQueriesNothingExtra(): void
    {
        $this->createUser('alice');

        $spy = new QuerySpy($this->queryBuilder());
        $criteria = (new Criteria())
            ->AddWith('posts')
            ->Add(new Criterion('username', QueryBuilderOperator::Equals, 'nobody'));

        $this->assertSame([], User::FindMany($criteria, $spy));
        $this->assertSame(1, $spy->selects(), 'Only the outer SELECT runs.');
    }

    public function testPagingAlsoEagerLoads(): void
    {
        $user = $this->createUser('alice');
        $this->createPost($user, 'one', 1);

        $spy = new QuerySpy($this->queryBuilder());
        $page = User::Paging(10, 0, (new Criteria())->AddWith('posts'), $spy);

        $this->assertCount(1, $page[0]->posts);
        $this->assertSame(2, $spy->selects());
    }

    public function testUnknownRelationNameThrows(): void
    {
        $this->createUser('alice');

        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage("'nope' is not a relation on");

        User::FindMany((new Criteria())->AddWith('nope'), $this->queryBuilder());
    }
}

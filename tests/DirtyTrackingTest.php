<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\Criterion;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;
use FishyBoat21\ExtendOrm\Tests\Models\User;
use FishyBoat21\ExtendOrm\Tests\Support\QuerySpy;

final class DirtyTrackingTest extends TestCase
{
    private function loadUser(int $id, ?QuerySpy $spy = null): User
    {
        $criteria = (new Criteria())->Add(new Criterion('id', QueryBuilderOperator::Equals, $id));
        $user = User::FindOne($criteria, $spy ?? $this->queryBuilder());
        $this->assertNotNull($user);

        return $user;
    }

    private function column(string $name, int $id): mixed
    {
        return $this->pdo->query("SELECT $name FROM users WHERE id = $id")->fetchColumn();
    }

    /**
     * The core reason for dirty tracking: a column another writer changed after
     * we loaded the row must not be written back from our stale copy.
     */
    public function testSaveDoesNotClobberColumnsChangedByAnotherWriter(): void
    {
        $this->createUser('alice', 'alice@example.com');
        $user = $this->loadUser(1);

        $this->pdo->exec("UPDATE users SET email = 'changed@example.com' WHERE id = 1");

        $user->username = 'alicia';
        $user->save();

        $this->assertSame('alicia', $this->column('username', 1));
        $this->assertSame('changed@example.com', $this->column('email', 1));
    }

    public function testSavingAnUnchangedModelIssuesNoWrite(): void
    {
        $this->createUser('alice');

        $spy = new QuerySpy($this->queryBuilder());
        $user = $this->loadUser(1, $spy);
        $this->assertSame(1, $spy->selects());

        $user->save();
        $this->assertSame(0, $spy->execs(), 'An untouched model must not write.');
        $this->assertSame(0, $spy->inserts());

        $user->username = 'alicia';
        $user->save();
        $this->assertSame(1, $spy->execs(), 'A changed model writes exactly once.');
    }

    public function testSavingTwiceWithoutFurtherEditsWritesOnlyOnce(): void
    {
        $this->createUser('alice');

        $spy = new QuerySpy($this->queryBuilder());
        $user = $this->loadUser(1, $spy);

        $user->username = 'alicia';
        $user->save();
        $user->save();

        $this->assertSame(1, $spy->execs());
    }

    /**
     * A model built by hand (never loaded) has no baseline, so save() must still
     * write the complete row — the behaviour that existed before dirty tracking.
     */
    public function testManuallyBuiltModelPerformsAFullWrite(): void
    {
        $this->createUser('alice', 'alice@example.com');

        $user = new User($this->queryBuilder());
        $user->id = 1;
        $user->username = 'alicia';
        $user->email = null;
        $user->save();

        $this->assertSame('alicia', $this->column('username', 1));
        $this->assertSame(
            1,
            (int) $this->pdo->query('SELECT COUNT(*) FROM users WHERE id = 1 AND email IS NULL')->fetchColumn()
        );
    }

    public function testNewModelIsInsertedOnce(): void
    {
        $spy = new QuerySpy($this->queryBuilder());
        $user = new User($spy);
        $user->username = 'alice';
        $user->save();

        $this->assertSame(1, $spy->inserts());
        $this->assertSame(0, $spy->execs());
    }

    public function testAFreshlyInsertedModelUpdatesRatherThanInsertsOnTheNextSave(): void
    {
        $this->createUser('alice');
        $user = $this->loadUser(1);

        $user->username = 'alicia';
        $user->save();

        $this->assertSame(1, $this->userCount(), 'The second save must update, not insert.');
    }
}

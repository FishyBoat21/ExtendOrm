<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Database;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilder2;
use FishyBoat21\ExtendOrm\Tests\Models\Post;
use FishyBoat21\ExtendOrm\Tests\Models\User;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Boots the ORM against a throwaway in-memory SQLite database for every test.
 */
abstract class TestCase extends BaseTestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        foreach ($this->statements($this->schema()) as $statement) {
            $this->pdo->exec($statement);
        }

        Database::Boot($this->pdo);
    }

    /**
     * The "order" column is deliberately a SQL reserved word, to prove identifier
     * quoting works.
     */
    protected function schema(): string
    {
        return <<<'SQL'
            CREATE TABLE "users" (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                email TEXT,
                created_at TEXT
            );
            CREATE TABLE "profiles" (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                phone TEXT NOT NULL
            );
            CREATE TABLE "posts" (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                "order" INTEGER NOT NULL DEFAULT 0
            );
            SQL;
    }

    protected function queryBuilder(): QueryBuilder2
    {
        return new QueryBuilder2($this->pdo);
    }

    protected function createUser(string $username, ?string $email = null): User
    {
        $user = new User($this->queryBuilder());
        $user->username = $username;
        $user->email = $email;
        $user->save();

        return $user;
    }

    protected function createPost(User $user, string $title, int $order = 0): Post
    {
        $post = new Post($this->queryBuilder());
        $post->userId = $user->id;
        $post->title = $title;
        $post->order = $order;
        $post->save();

        return $post;
    }

    protected function userCount(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /**
     * @return list<string>
     */
    private function statements(string $sql): array
    {
        return array_values(array_filter(
            array_map('trim', explode(';', $sql)),
            static fn (string $statement): bool => $statement !== ''
        ));
    }
}

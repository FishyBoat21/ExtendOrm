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
 * Boots the ORM against a throwaway database for every test.
 *
 * By default that is an in-memory SQLite database. Point EXTENDORM_TEST_DSN at a
 * MySQL/MariaDB database to run the identical suite against it:
 *
 *   EXTENDORM_TEST_DSN='mysql:host=127.0.0.1;port=3306;dbname=extendorm_test;charset=utf8mb4' \
 *   EXTENDORM_TEST_USER=root EXTENDORM_TEST_PASSWORD= vendor/bin/phpunit
 */
abstract class TestCase extends BaseTestCase
{
    /** Created by schema(), and reset before each test, in creation order. */
    private const TABLES = ['users', 'profiles', 'posts'];

    /** A persistent server keeps its tables across tests, so build them once. */
    private static bool $schemaCreated = false;

    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = $this->connect();
        $this->prepareSchema();

        Database::Boot($this->pdo);
    }

    private function prepareSchema(): void
    {
        // An in-memory SQLite connection starts empty every single time, so the
        // create-once shortcut would leave it with no tables.
        $ephemeral = $this->driver() === 'sqlite';

        if (!$ephemeral && self::$schemaCreated) {
            $this->truncateTables();
            return;
        }

        $this->dropTables();
        foreach ($this->statements($this->schema()) as $statement) {
            $this->pdo->exec($statement);
        }
        self::$schemaCreated = true;
    }

    private function truncateTables(): void
    {
        $quote = $this->driver() === 'mysql' ? '`' : '"';
        foreach (array_reverse(self::TABLES) as $table) {
            // TRUNCATE resets AUTO_INCREMENT too, which the id assertions rely on.
            $this->pdo->exec("TRUNCATE TABLE {$quote}{$table}{$quote}");
        }
    }

    protected function connect(): PDO
    {
        $dsn = getenv('EXTENDORM_TEST_DSN');
        if (!is_string($dsn) || $dsn === '') {
            $dsn = 'sqlite::memory:';
        }
        $user = getenv('EXTENDORM_TEST_USER');
        $password = getenv('EXTENDORM_TEST_PASSWORD');

        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        if (str_starts_with($dsn, 'mysql:')) {
            // Ask the server for native column types instead of strings.
            $options[PDO::ATTR_EMULATE_PREPARES] = false;
        }

        return new PDO(
            $dsn,
            is_string($user) ? $user : null,
            is_string($password) ? $password : null,
            $options
        );
    }

    protected function driver(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    private function dropTables(): void
    {
        $quote = $this->driver() === 'mysql' ? '`' : '"';
        foreach (array_reverse(self::TABLES) as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS {$quote}{$table}{$quote}");
        }
    }

    protected function schema(): string
    {
        return $this->driver() === 'mysql' ? $this->mysqlSchema() : $this->sqliteSchema();
    }

    /**
     * The "order" column is deliberately a SQL reserved word, to prove identifier
     * quoting works.
     */
    protected function sqliteSchema(): string
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

    protected function mysqlSchema(): string
    {
        return <<<'SQL'
            CREATE TABLE `users` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `username` VARCHAR(191) NOT NULL,
                `email` VARCHAR(191) DEFAULT NULL,
                `created_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            CREATE TABLE `profiles` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `phone` VARCHAR(191) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            CREATE TABLE `posts` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `title` VARCHAR(191) NOT NULL,
                `order` INT NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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

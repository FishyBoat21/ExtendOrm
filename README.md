# ExtendOrm

<div align="center">

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)
[![Composer](https://img.shields.io/badge/Composer-fishyboat21/extendorm-blue?style=for-the-badge&logo=composer&logoColor=white)](https://packagist.org/packages/fishyboat21/extendorm)

**A Simple, Lightweight CRUD ORM for PHP 8.1+**

</div>

---

## 📖 Table of Contents

- [About](#-about)
- [Features](#-features)
- [Requirements](#-requirements)
- [Database Compatibility](#-database-compatibility)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Documentation](#-documentation)
  - [Database Connection](#database-connection)
  - [Defining Models](#defining-models)
  - [Attributes](#attributes)
  - [Relationships](#relationships)
  - [Eager Loading](#eager-loading)
  - [CRUD Operations](#crud-operations)
  - [Query Builder](#query-builder)
  - [Transactions](#transactions)
  - [Testing](#testing)
- [Examples](#-examples)
- [API Reference](#-api-reference)
- [Contributing](#-contributing)
- [License](#-license)

---

## 📌 About

**ExtendOrm** is a lightweight, easy-to-use Object-Relational Mapping (ORM) library for PHP 8.1+. It provides a simple yet powerful way to interact with your database using PHP objects, with support for relationships, query building, and transactions.

Perfect for developers who want ORM functionality without the complexity and overhead of heavier solutions like Doctrine or Eloquent.

---

## ✨ Features

- 🚀 **Lightweight & Fast** - Minimal overhead, no bloat
- 🔧 **PHP Attributes** - Clean, modern syntax for model definitions
- 🔗 **Relationships** - HasOne, HasMany, BelongsTo support
- 📝 **CRUD Operations** - Simple save, find, update, delete methods
- 🔍 **Query Builder** - Fluent interface with `IN`, `BETWEEN` and `OR` conditions
- ⚡ **Eager Loading** - `with()` preloads a relation in one query for the whole result set
- 🔒 **Transactions** - Automatic rollback on errors, with nested savepoints
- 📦 **Pagination** - Built-in support for paginated results
- 🧮 **Dirty Tracking** - `save()` writes only the columns you actually changed
- 🗄️ **Dialect Layer** - Identifier quoting follows the active PDO driver
- 🎯 **Type-Safe** - Leverages PHP's type system
- 💾 **PDO-Based** - Built on plain PDO; MySQL/MariaDB fully supported, other drivers portable but unverified

---

## 📋 Requirements

- **PHP** 8.1 or higher
- **PDO** extension enabled
- **Database**: MySQL or MariaDB (fully supported)

> ⚠️ **Important Note**: Identifiers are quoted using the active PDO driver's convention and paging uses the portable `LIMIT n OFFSET m` form. MariaDB (in CI) and SQLite (by default) are both exercised by the test suite; PostgreSQL should work but has no integration test yet. See *Database Compatibility* below.

---

## 🗄️ Database Compatibility

| Database | Status | Notes |
|----------|--------|-------|
| **MariaDB** | ✅ Verified | The whole suite passes against MariaDB 12.3, and against MariaDB in CI |
| **MySQL** | ✅ Expected | Not separately verified — the CI service is MariaDB, which shares MySQL's driver |
| **SQLite** | ✅ Verified | Backs the default in-memory test database |
| **PostgreSQL** | ⚠️ Unverified | Quoting and paging are portable, but there is no integration test yet |
| **SQL Server** | ❌ Not Tested | Unconfirmed compatibility |
| **Oracle** | ❌ Not Tested | Unconfirmed compatibility |

### Dialects

Identifier quoting is delegated to a `Dialect` resolved from the PDO connection:

| Driver | Dialect | Identifiers are quoted with |
|--------|---------|-----------------------------|
| `mysql` | `MySqlDialect` | backticks |
| anything else | `AnsiDialect` | double quotes |

Pass your own implementation if you need different behaviour:

```php
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilder2;
use FishyBoat21\ExtendOrm\Dialect\MySqlDialect;

$qb = new QueryBuilder2($pdo, new MySqlDialect());
```

### Driver-Specific Notes

The builder emits portable SQL for everything it constructs: identifier quoting follows the active PDO driver, and paging uses `LIMIT n OFFSET m`. The items below concern your own schema and raw SQL:

```sql
-- Identifier quoting is emitted per driver
`column_name`      -- MySQL / MariaDB
"column_name"      -- SQLite / PostgreSQL

-- Auto-increment
AUTO_INCREMENT

-- Date/Time functions
NOW(), CURDATE()
```

### For Other Databases

If you hand-write raw SQL alongside the ORM, keep the following in mind. The ORM's own queries already handle the first two:

1. **LIMIT/OFFSET Syntax**
   ```sql
   -- MySQL: LIMIT offset, count
   LIMIT 0, 10
   
   -- PostgreSQL/SQLite: LIMIT count OFFSET offset
   LIMIT 10 OFFSET 0
   ```

2. **Identifier Quoting**
   ```sql
   -- MySQL: backticks
   `column_name`
   
   -- PostgreSQL/SQLite: double quotes
   "column_name"
   ```

3. **Auto-increment Primary Keys**
   ```sql
   -- MySQL: AUTO_INCREMENT
   id INT AUTO_INCREMENT PRIMARY KEY
   
   -- PostgreSQL: SERIAL
   id SERIAL PRIMARY KEY
   
   -- SQLite: AUTOINCREMENT
   id INTEGER PRIMARY KEY AUTOINCREMENT
   ```

---

## 📦 Installation

### Via Composer (Recommended)

```bash
composer require fishyboat21/extendorm
```

> **Note**: every release so far is a pre-release (`2.0.0-alpha.x`), which
> Composer's default stability will refuse. Either allow pre-releases for this
> package:
>
> ```bash
> composer require fishyboat21/extendorm:^2.0@alpha
> ```
>
> or set `"minimum-stability": "alpha"` in your own `composer.json`.

### Manual Installation

1. Clone the repository:
```bash
git clone https://github.com/FishyBoat21/ExtendOrm.git
```

2. Include the autoloader in your project:
```php
require_once 'vendor/autoload.php';
```

3. Or manually load the classes:
```php
spl_autoload_register(function ($class) {
    $prefix = 'FishyBoat21\\ExtendOrm\\';
    $base_dir = __DIR__ . '/ExtendOrm/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
```

---

## ⚡ Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use FishyBoat21\ExtendOrm\Database;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilder2;
use FishyBoat21\ExtendOrm\Attribute\Table;
use FishyBoat21\ExtendOrm\Attribute\Column;
use FishyBoat21\ExtendOrm\Attribute\PrimaryKey;
use FishyBoat21\ExtendOrm\Model;
use PDO;

// 1. Setup database connection (MySQL example)
$pdo = new PDO(
    'mysql:host=localhost;dbname=mydb;charset=utf8mb4',
    'username',
    'password',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// For PostgreSQL:
// $pdo = new PDO('pgsql:host=localhost;dbname=mydb', 'username', 'password');

// For SQLite:
// $pdo = new PDO('sqlite:/path/to/database.sqlite');

// 2. Boot the ORM
Database::Boot($pdo);

// 3. Create query builder
$qb = new QueryBuilder2();

// 4. Define your model
#[Table('users')]
class User extends Model {
    #[PrimaryKey]
    #[Column('id')]
    public int $id;
    
    #[Column('username')]
    public string $username;
    
    #[Column('email')]
    public string $email;
}

// 5. Create and save a record
$user = new User($qb);
$user->username = 'johndoe';
$user->email = 'john@example.com';
$user->save();

// 6. Find records
use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\Criterion;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;

$criteria = new Criteria();
$criteria->Add(new Criterion('id', QueryBuilderOperator::Equals, 1));

$foundUser = User::FindOne($criteria, $qb);
echo $foundUser->username; // Output: johndoe
```

---

## 📚 Documentation

### Database Connection

Initialize the database connection using the singleton `Database` class:

```php
use FishyBoat21\ExtendOrm\Database;
use PDO;

// MySQL
$pdo = new PDO(
    'mysql:host=localhost;dbname=mydb;charset=utf8mb4',
    'username',
    'password',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// PostgreSQL
// $pdo = new PDO(
//     'pgsql:host=localhost;dbname=mydb',
//     'username',
//     'password',
//     [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
// );

// SQLite
// $pdo = new PDO('sqlite:/path/to/database.sqlite');

// Boot the ORM with your PDO connection
Database::Boot($pdo);

// Get instance later in your code
$db = Database::GetInstance();
$connection = $db->GetConnection();
```

---

### Defining Models

Extend the abstract `Model` class and use PHP attributes to map your database tables:

```php
use FishyBoat21\ExtendOrm\Model;
use FishyBoat21\ExtendOrm\Attribute\Table;
use FishyBoat21\ExtendOrm\Attribute\Column;
use FishyBoat21\ExtendOrm\Attribute\PrimaryKey;

#[Table('users')]
class User extends Model {
    #[PrimaryKey]
    #[Column('id')]
    public int $id;
    
    #[Column('username')]
    public string $username;
    
    #[Column('email')]
    public string $email;
    
    #[Column('created_at')]
    public ?string $created_at = null;
}
```

---

### Attributes

| Attribute | Description | Example |
|-----------|-------------|---------|
| `#[Table('name')]` | Defines the database table name | `#[Table('users')]` |
| `#[Column('field')]` | Maps property to database column | `#[Column('user_name')]` |
| `#[PrimaryKey]` | Marks the primary key field | `#[PrimaryKey] #[Column('id')]` |
| `#[Relation(...)]` | Defines relationships | See below |

---

### Relationships

ExtendOrm supports three relationship types using the `#[Relation]` attribute.

#### RelationType Enum

```php
use FishyBoat21\ExtendOrm\Attribute\Relation\RelationType;

RelationType::HasOne      // One-to-one
RelationType::HasMany     // One-to-many
RelationType::BelongsTo   // Many-to-one
```

#### HasOne Relationship

A user has one profile:

```php
use FishyBoat21\ExtendOrm\Attribute\Relation;
use FishyBoat21\ExtendOrm\Attribute\Relation\RelationType;

#[Table('users')]
class User extends Model {
    #[PrimaryKey]
    #[Column('id')]
    public int $id;
    
    #[Column('username')]
    public string $username;
    
    #[Relation(
        type: RelationType::HasOne,
        target: Profile::class,
        foreignKey: 'user_id',
        localKey: 'id',
        ownerKey: 'id'
    )]
    public ?Profile $profile;
}
```

#### HasMany Relationship

A user has many posts:

```php
#[Table('users')]
class User extends Model {
    #[PrimaryKey]
    #[Column('id')]
    public int $id;
    
    #[Relation(
        type: RelationType::HasMany,
        target: Post::class,
        foreignKey: 'user_id',
        localKey: 'id',
        ownerKey: 'id'
    )]
    public array $posts;
}
```

#### BelongsTo Relationship

A post belongs to a user:

```php
#[Table('posts')]
class Post extends Model {
    #[PrimaryKey]
    #[Column('id')]
    public int $id;
    
    #[Column('user_id')]
    public int $user_id;
    
    #[Relation(
        type: RelationType::BelongsTo,
        target: User::class,
        foreignKey: 'user_id',
        localKey: 'user_id',
        ownerKey: 'id'
    )]
    public ?User $author;
}
```

#### Complete Model with Multiple Relationships

```php
#[Table('posts')]
class Post extends Model {
    #[PrimaryKey]
    #[Column('id')]
    public int $id;
    
    #[Column('user_id')]
    public int $user_id;
    
    #[Column('category_id')]
    public ?int $category_id = null;
    
    #[Column('title')]
    public string $title;
    
    #[Column('content')]
    public string $content;
    
    #[Column('published_at')]
    public ?string $published_at = null;
    
    // BelongsTo: Post belongs to User (author)
    #[Relation(
        type: RelationType::BelongsTo,
        target: User::class,
        foreignKey: 'user_id',
        localKey: 'user_id',
        ownerKey: 'id'
    )]
    public ?User $author;
    
    // BelongsTo: Post belongs to Category
    #[Relation(
        type: RelationType::BelongsTo,
        target: Category::class,
        foreignKey: 'category_id',
        localKey: 'category_id',
        ownerKey: 'id'
    )]
    public ?Category $category;
    
    // HasMany: Post has many Comments
    #[Relation(
        type: RelationType::HasMany,
        target: Comment::class,
        foreignKey: 'post_id',
        localKey: 'id',
        ownerKey: 'id'
    )]
    public array $comments;
}
```

---

### Eager Loading

Touching a relation runs a query the first time it is accessed, so iterating a
result set costs one extra query per model — the classic N+1. `AddWith()`
preloads relations for the whole set, one query per relation:

```php
$criteria = (new Criteria())->AddWith('posts', 'profile');

foreach (User::FindMany($criteria, $qb) as $user) {
    echo $user->posts[0]->title;   // already loaded, no extra query
}
```

The loop above costs 3 queries with `AddWith` — one for the users, one for all
their posts, one for all their profiles — against `1 + N` without it.

`FindOne()` and `Paging()` honour `AddWith()` as well. Nested paths such as
`AddWith('posts.author')` are not supported yet.

---

### CRUD Operations

#### Create (Insert)

```php
$user = new User($qb);
$user->username = 'johndoe';
$user->email = 'john@example.com';
$user->save(); // Performs INSERT

echo $user->id; // Auto-generated primary key
```

#### Read (Find)

```php
use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\Criterion;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;

// Find one record
$criteria = new Criteria();
$criteria->Add(new Criterion('id', QueryBuilderOperator::Equals, 1));
$user = User::FindOne($criteria, $qb);

// Find many records
$criteria = new Criteria();
$criteria->Add(new Criterion('email', QueryBuilderOperator::Like, '%@gmail.com'));
$users = User::FindMany($criteria, $qb);

foreach ($users as $user) {
    echo $user->username . "\n";
}

// Pagination (limit, offset, criteria, queryBuilder)
$posts = Post::Paging(10, 20, $criteria, $qb); // Page 3, 10 per page
```

#### Update

```php
// Find the record first
$criteria = new Criteria();
$criteria->Add(new Criterion('id', QueryBuilderOperator::Equals, 1));
$user = User::FindOne($criteria, $qb);

// Modify and save
$user->username = 'updated_name';
$user->email = 'newemail@example.com';
$user->save(); // Performs UPDATE (primary key exists)
```

Only the columns you actually changed are written, so a value another writer
updated after you loaded the row is not overwritten from your stale copy. Saving
a model you did not edit issues no statement at all.

#### Delete

```php
$criteria = new Criteria();
$criteria->Add(new Criterion('id', QueryBuilderOperator::Equals, 1));
$user = User::FindOne($criteria, $qb);

if ($user) {
    $user->delete();
}
```

---

### Query Builder

#### Available Operators

```php
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;

QueryBuilderOperator::Equals        // =
QueryBuilderOperator::NotEqual      // !=
QueryBuilderOperator::LessThan      // <
QueryBuilderOperator::MoreThan      // >
QueryBuilderOperator::LessThanEquals// <=
QueryBuilderOperator::MoreThanEquals// >=
QueryBuilderOperator::Like          // LIKE
QueryBuilderOperator::NotLike       // NOT LIKE
QueryBuilderOperator::Is            // IS (for NULL checks)
```

#### Sorting

```php
use FishyBoat21\ExtendOrm\Sort;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderSortType;

$criteria->AddSort(new Sort('created_at', QueryBuilderSortType::Descending));
$criteria->AddSort(new Sort('name', QueryBuilderSortType::Ascending));
```

#### Building Complex Criteria

```php
use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\Criterion;
use FishyBoat21\ExtendOrm\Sort;

$criteria = new Criteria();

// Add multiple conditions
$criteria->Add(new Criterion('status', QueryBuilderOperator::Equals, 'active'));
$criteria->Add(new Criterion('age', QueryBuilderOperator::MoreThanEquals, 18));
$criteria->Add(new Criterion('email', QueryBuilderOperator::Like, '%@example.com'));

// Add sorting
$criteria->AddSort(new Sort('created_at', QueryBuilderSortType::Descending));
$criteria->AddSort(new Sort('name', QueryBuilderSortType::Ascending));

// Execute query
$users = User::FindMany($criteria, $qb);
```

---

#### Conditions: IN, BETWEEN and OR

```php
use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\Criterion;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;

$criteria = new Criteria();
$criteria->Add(new Criterion('id', QueryBuilderOperator::In, [1, 2, 3]));
$criteria->Add(new Criterion('age', QueryBuilderOperator::Between, [18, 30]));

// AddOr() joins a condition with OR instead of AND.
$criteria->AddOr(new Criterion('role', QueryBuilderOperator::Equals, 'admin'));
```

> **Note**: only AND/OR connectors are supported — there are no nested groups yet,
> so standard SQL precedence applies. `Add(a)`, `Add(b)`, `AddOr(c)` compiles to
> `a AND b OR c`, which SQL reads as `(a AND b) OR c`.

---

### Transactions

Execute multiple operations in a transaction with automatic rollback on error:

```php
use FishyBoat21\ExtendOrm\Database;

$db = Database::GetInstance();

$db->Transaction(function($pdo) use ($qb) {
    // All operations in this closure run in a transaction
    $user = new User($qb);
    $user->username = 'newuser';
    $user->email = 'new@example.com';
    $user->save();
    
    $profile = new Profile($qb);
    $profile->user_id = $user->id;
    $profile->phone = '+1-555-0123';
    $profile->save();
    
    // If any exception occurs, everything rolls back automatically
    // No need to manually commit or rollback
});
```

---

## 💡 Examples

### Blog System - Complete Example

```php
<?php
require_once 'vendor/autoload.php';

use FishyBoat21\ExtendOrm\Database;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilder2;
use FishyBoat21\ExtendOrm\Model;
use FishyBoat21\ExtendOrm\Attribute\Table;
use FishyBoat21\ExtendOrm\Attribute\Column;
use FishyBoat21\ExtendOrm\Attribute\PrimaryKey;
use FishyBoat21\ExtendOrm\Attribute\Relation;
use FishyBoat21\ExtendOrm\Attribute\Relation\RelationType;
use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\Criterion;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;
use PDO;

// Setup
$pdo = new PDO('mysql:host=localhost;dbname=blog', 'user', 'pass');
Database::Boot($pdo);
$qb = new QueryBuilder2();

// Define Models
#[Table('users')]
class User extends Model {
    #[PrimaryKey] #[Column('id')] public int $id;
    #[Column('username')] public string $username;
    #[Column('email')] public string $email;
    
    #[Relation(
        type: RelationType::HasMany,
        target: Post::class,
        foreignKey: 'user_id',
        localKey: 'id',
        ownerKey: 'id'
    )]
    public array $posts;
}

#[Table('posts')]
class Post extends Model {
    #[PrimaryKey] #[Column('id')] public int $id;
    #[Column('user_id')] public int $user_id;
    #[Column('title')] public string $title;
    #[Column('content')] public string $content;
    #[Column('published_at')] public ?string $published_at = null;
    
    #[Relation(
        type: RelationType::BelongsTo,
        target: User::class,
        foreignKey: 'user_id',
        localKey: 'user_id',
        ownerKey: 'id'
    )]
    public ?User $author;
}

// Create a user and post
$user = new User($qb);
$user->username = 'johndoe';
$user->email = 'john@example.com';
$user->save();

$post = new Post($qb);
$post->user_id = $user->id;
$post->title = 'My First Post';
$post->content = 'Hello, world!';
$post->published_at = date('Y-m-d H:i:s');
$post->save();

// Query with relationships
$criteria = new Criteria();
$criteria->Add(new Criterion('user_id', QueryBuilderOperator::Equals, $user->id));

$posts = Post::FindMany($criteria, $qb);

foreach ($posts as $post) {
    echo $post->title . " by " . $post->author->username . "\n";
}
```

### Advanced Filtering and Pagination

```php
// Find published posts with more than 100 views, sorted by date
$criteria = new Criteria();
$criteria->Add(new Criterion('published', QueryBuilderOperator::Equals, 1));
$criteria->Add(new Criterion('views', QueryBuilderOperator::MoreThan, 100));
$criteria->AddSort(new Sort('published_at', QueryBuilderSortType::Descending));

// Get page 3 with 10 items per page
$posts = Post::Paging(10, 20, $criteria, $qb);

foreach ($posts as $post) {
    echo $post->title . "\n";
}
```

---

## 📖 API Reference

### Model Class

| Method | Description | Returns |
|--------|-------------|---------|
| `save()` | Insert or update record based on primary key | `self` |
| `delete()` | Delete record from database | `void` |
| `FindOne(Criteria $criteria, IQueryBuilder2 $qb)` | Find single record matching criteria | `?static` |
| `FindMany(Criteria $criteria, IQueryBuilder2 $qb)` | Find multiple records matching criteria | `array` |
| `Paging(int $limit, int $offset, Criteria $criteria, IQueryBuilder2 $qb)` | Get paginated results | `array` |
| `GetTableName()` | Get the table name for the model | `string` |

### Database Class

| Method | Description | Returns |
|--------|-------------|---------|
| `Boot(PDO $connection)` | Initialize the ORM with PDO connection | `void` |
| `GetInstance()` | Get the singleton database instance | `Database` |
| `GetConnection()` | Get the PDO connection | `PDO` |
| `Transaction(callable $function)` | Execute operations in a transaction | `mixed` |

### Criteria Class

| Method | Description | Returns |
|--------|-------------|---------|
| `Add(Criterion $criterion)` | Add a condition joined with AND | `self` |
| `AddOr(Criterion $criterion)` | Add a condition joined with OR | `self` |
| `AddSort(Sort $sort)` | Add sorting order | `self` |
| `AddWith(string ...$relations)` | Preload relations for the result set | `self` |

### QueryBuilderOperator Enum

| Value | SQL Equivalent |
|-------|---------------|
| `Equals` | `=` |
| `NotEqual` | `!=` |
| `LessThan` | `<` |
| `MoreThan` | `>` |
| `LessThanEquals` | `<=` |
| `MoreThanEquals` | `>=` |
| `Like` | `LIKE` |
| `NotLike` | `NOT LIKE` |
| `Is` | `IS` (NULL only — throws otherwise) |
| `IsNull` | `IS NULL` |
| `IsNotNull` | `IS NOT NULL` |
| `In` / `NotIn` | `IN (...)` / `NOT IN (...)` — value is a non-empty array |
| `Between` / `NotBetween` | `BETWEEN ? AND ?` — value is `[minimum, maximum]` |

### QueryBuilderSortType Enum

| Value | Description |
|-------|-------------|
| `Ascending` | ORDER BY ... ASC |
| `Descending` | ORDER BY ... DESC |

---

## 🗂️ Project Structure

```
ExtendOrm/
├── src/
│   ├── Attribute/
│   │   ├── Column.php
│   │   ├── PrimaryKey.php
│   │   ├── Table.php
│   │   ├── Relation.php
│   │   └── Relation/
│   │       └── RelationType.php
│   ├── Dialect/
│   │   ├── Dialect.php
│   │   ├── AbstractDialect.php
│   │   ├── MySqlDialect.php
│   │   ├── AnsiDialect.php
│   │   └── DialectFactory.php
│   ├── QueryBuilder2/
│   │   ├── QueryBuilder2.php
│   │   ├── IQueryBuilder2.php
│   │   ├── QueryBuilderOperator.php
│   │   └── QueryBuilderSortType.php
│   ├── Model.php
│   ├── ModelMap.php
│   ├── Database.php
│   ├── Criteria.php
│   ├── Criterion.php
│   ├── Sort.php
│   └── ExtendORMException.php
├── tests/
│   ├── Models/          # Fixture models used by the suite
│   ├── Support/         # Test helpers (e.g. QuerySpy)
│   └── *Test.php
├── .github/workflows/ci.yml
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── README.md
└── LICENSE
```

---

## 🧪 Testing

The suite runs against an in-memory SQLite database by default, so it needs no server:

```bash
composer test        # PHPUnit
composer analyse     # PHPStan (level 5)
```

Point it at a MySQL/MariaDB server to run the identical suite there:

```bash
EXTENDORM_TEST_DSN='mysql:host=127.0.0.1;port=3306;dbname=extendorm_test;charset=utf8mb4' \
EXTENDORM_TEST_USER=root \
EXTENDORM_TEST_PASSWORD= \
composer test
```

The database named in the DSN must already exist — the suite creates and resets
its own tables inside it. CI runs the SQLite matrix on PHP 8.1–8.4 plus a
MariaDB job, so both supported drivers are covered — see
[.github/workflows/ci.yml](.github/workflows/ci.yml).

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### How to Contribute

1. **Fork** the repository
2. **Create** your feature branch (`git checkout -b feature/AmazingFeature`)
3. **Commit** your changes (`git commit -m 'Add some AmazingFeature'`)
4. **Push** to the branch (`git push origin feature/AmazingFeature`)
5. **Open** a Pull Request

### Guidelines

- Follow PSR-12 coding standards
- Add tests for new features — see [Testing](#-testing); `composer test` must stay green
- Update documentation as needed
- Keep commits atomic and descriptive
- **Note**: If adding support for non-MySQL databases, please include appropriate tests and documentation

---

## 📄 License

This project is open-sourced software licensed under the [MIT License](LICENSE).

```
MIT License

Copyright (c) 2024-2026 FishyBoat21

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 🙏 Acknowledgments

- Inspired by **Laravel Eloquent** and **Doctrine ORM**
- Built with ❤️ using **PHP 8.1+** features (Attributes, Enums, Typed Properties)
- Thanks to all contributors and users!

---

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/FishyBoat21/ExtendOrm/issues)
- **Source**: [GitHub Repository](https://github.com/FishyBoat21/ExtendOrm)
- **Author**: [FishyBoat21](https://github.com/FishyBoat21)

---

<div align="center">

**Made by [FishyBoat21](https://github.com/FishyBoat21)**

⭐ **Star this repo if you find it useful!**

</div>
# ExtendOrm

<div align="center">

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--3.0-blue?style=for-the-badge)](LICENSE)
[![Composer](https://img.shields.io/badge/Composer-fishyboat21/extendorm-blue?style=for-the-badge&logo=composer&logoColor=white)](https://packagist.org/packages/fishyboat21/extendorm)

**A Simple, Lightweight CRUD ORM for PHP 8+**

</div>

---

## 📖 Table of Contents

- [About](#-about)
- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Documentation](#-documentation)
  - [Database Connection](#database-connection)
  - [Defining Models](#defining-models)
  - [Attributes](#attributes)
  - [Relationships](#relationships)
  - [CRUD Operations](#crud-operations)
  - [Query Builder](#query-builder)
  - [Transactions](#transactions)
- [Examples](#-examples)
- [API Reference](#-api-reference)
- [Contributing](#-contributing)
- [License](#-license)

---

## 📌 About

**ExtendOrm** is a lightweight, easy-to-use Object-Relational Mapping (ORM) library for PHP 8.0+. It provides a simple yet powerful way to interact with your database using PHP objects, with support for relationships, query building, and transactions.

Perfect for developers who want ORM functionality without the complexity and overhead of heavier solutions like Doctrine or Eloquent.

---

## ✨ Features

- 🚀 **Lightweight & Fast** - Minimal overhead, no bloat
- 🔧 **PHP 8+ Attributes** - Clean, modern syntax for model definitions
- 🔗 **Relationships** - HasOne, HasMany, BelongsTo support
- 📝 **CRUD Operations** - Simple save, find, update, delete methods
- 🔍 **Query Builder** - Fluent interface for complex queries
- 🔒 **Transactions** - Automatic rollback on errors
- 📦 **Pagination** - Built-in support for paginated results
- 🎯 **Type-Safe** - Leverages PHP's type system
- 💾 **PDO-Based** - Works with MySQL, PostgreSQL, SQLite, and more

---

## 📋 Requirements

- **PHP** 8.0 or higher
- **PDO** extension enabled
- **Database**: MySQL, PostgreSQL, SQLite, or any PDO-compatible database

---

## 📦 Installation

### Via Composer (Recommended)

```bash
composer require fishyboat21/extendorm
```

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

// 1. Setup database connection
$pdo = new PDO(
    'mysql:host=localhost;dbname=mydb;charset=utf8mb4',
    'username',
    'password',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

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

$pdo = new PDO(
    'mysql:host=localhost;dbname=mydb;charset=utf8mb4',
    'username',
    'password',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

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
| `Paging(int $limit, int $offset, Criteria $criteria, QueryBuilder2 $qb)` | Get paginated results | `array` |
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
| `Add(Criterion $criterion)` | Add a where condition | `self` |
| `AddSort(Sort $sort)` | Add sorting order | `self` |

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
| `Is` | `IS` (for NULL) |

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
├── composer.json
├── README.md
└── LICENSE
```

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
- Add tests for new features
- Update documentation as needed
- Keep commits atomic and descriptive

---

## 📄 License

This project is open-sourced software licensed under the [GNU General Public License v3.0](LICENSE).

```
Copyright (C) 2024 FishyBoat21

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
```

---

## 🙏 Acknowledgments

- Inspired by **Laravel Eloquent** and **Doctrine ORM**
- Built with ❤️ using **PHP 8+** features (Attributes, Enums, Typed Properties)
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
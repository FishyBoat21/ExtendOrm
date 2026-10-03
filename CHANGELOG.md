# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This changelog starts at 2.0.0-alpha.3; earlier releases predate it.

## [2.0.0-alpha.3] - 2026-10-03

A hardening pass across correctness, testability and portability.

### Added

- **Test suite and CI.** A PHPUnit suite that runs against an in-memory SQLite
  database by default, and a GitHub Actions workflow that runs it on PHP 8.1,
  8.2, 8.3 and 8.4 plus a separate job running the same suite against a MariaDB
  service container. Set `EXTENDORM_TEST_DSN`, `EXTENDORM_TEST_USER` and
  `EXTENDORM_TEST_PASSWORD` to run the suite against your own MySQL/MariaDB
  server.
- **Dialect layer** (`src/Dialect/`). SQL rendering that differs between drivers
  now sits behind a `Dialect` interface with `MySqlDialect` and `AnsiDialect`
  implementations, resolved from the connection or passed explicitly as
  `new QueryBuilder2($pdo, $dialect)`.
- **Eager loading.** `Criteria::AddWith('posts', 'profile')` preloads relations
  for a whole result set — one query per relation instead of one per model.
  Honoured by `FindMany()`, `FindOne()` and `Paging()`.
- **`IN`, `NOT IN`, `BETWEEN` and `NOT BETWEEN` operators**, taking an array
  value. `IN`/`NOT IN` require a non-empty array; `BETWEEN`/`NOT BETWEEN`
  require `[minimum, maximum]`.
- **`OR` conditions**, via `Criteria::AddOr()` and `QueryBuilder2::orWhere()`.
- **`QueryBuilderOperator::IsNull` and `IsNotNull`**, so null checks need no
  bound parameter.
- **Dirty tracking.** `save()` remembers the row as loaded and writes only the
  columns that actually changed.
- **`IQueryBuilder2::newQuery()`**, returning a fresh builder over the same
  connection and dialect. Models use it, so a model query no longer resets the
  builder you passed in.
- **`Database::Reset()`**, to forget the current connection.
- `ModelMap::$Table` and `ModelMap::$PropColumnMap` (property → column),
  alongside the existing column → property map.

### Changed

- **The PHP floor is lowered from 8.4 to 8.1.** Nothing in the source used a
  post-8.1 language feature, so this widens who can install the library; CI
  verifies the whole range.
- Identifiers are quoted using the active PDO driver's convention (backticks on
  MySQL/MariaDB, double quotes elsewhere), so tables and columns whose names are
  SQL reserved words now work.
- Criteria and sort keys may be given as either the PHP property name or the raw
  column name.
- SQL text is no longer embedded in exception messages. The failing statement is
  written to the error log and the original `PDOException` is rethrown, so the
  driver error code and message survive.
- `save()` and `delete()` build their statement through `newQuery()`.

### Fixed

- **Criterion and sort keys were resolved with an unguarded `array_search()`.**
  A key that matched nothing collapsed to an empty column name and produced
  malformed SQL. It now throws `ExtendORMException` naming the mapped properties
  and columns.
- **`Model::Initialize()` rebuilt the reflection map on every `Find*` call**,
  despite being documented as a one-time cache. Reflection now runs once per
  model class.
- **Relations resolved their keys by reading an undefined property**, so a
  relation whose foreign-key column differed from its PHP property name silently
  queried with `null`. Keys are now translated through the column map.
- **Identifiers were interpolated into SQL unquoted and unvalidated**, which
  broke any table or column named after a reserved word and let caller-supplied
  identifiers reach the statement text.

### Removed

- **The 1.x `src/QueryBuilder/` tree** (9 files), dead since the 2.0 rewrite and
  still autoloadable by accident.
- **`QueryBuilder2\QueryBuilderJoinType`**, an enum with no `join()` method to
  use it.
- **`Model::GetPrimaryKey()`** and the vestigial `Model::$Table` and
  `Model::$IsInitialize` statics — written but never read, and shared across all
  subclasses as single inherited statics.

### Breaking changes

- `IQueryBuilder2` gained `newQuery()` and `orWhere()`. A custom implementation
  must add both.
- A relation declaration that omits a required key now throws at first use:
  `type`, `target` and `foreignKey` for any relation, `ownerKey` for
  `BelongsTo`, and `localKey` for `HasOne`/`HasMany`. These previously produced a
  query against an empty column name.
- A primary key property must also be mapped with `#[Column]`; it previously
  collapsed to an empty column name.
- `QueryBuilderOperator::Is` now accepts only `null` and throws otherwise. Use
  `IsNull`/`IsNotNull` for null checks.
- `save()` writes only changed columns, and issues no statement at all when
  nothing changed.
- `QueryBuilder2::__construct()` takes an optional second argument; existing
  calls are unaffected.

### Known limitations

- Eager loading does not support nested paths such as `AddWith('posts.author')`.
- Conditions support flat `AND`/`OR` connectors only — there is no nested
  grouping, so standard SQL precedence applies (`a AND b OR c` reads as
  `(a AND b) OR c`).
- No attribute casts, automatic timestamps, or soft deletes.
- Primary keys must be integers; `save()` inserts via `lastInsertId()`.
- PostgreSQL is expected to work but has no integration test.

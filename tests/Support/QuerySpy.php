<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests\Support;

use FishyBoat21\ExtendOrm\QueryBuilder2\IQueryBuilder2;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderSortType;

/**
 * Counters shared by a builder and every builder derived from it via newQuery().
 */
final class QueryCounter
{
    public int $selects = 0;
    public int $inserts = 0;
    public int $execs = 0;
}

/**
 * Decorates a query builder and counts what actually reaches the database, so
 * relation memoization, eager loading and dirty-tracked saves can be asserted on.
 */
final class QuerySpy implements IQueryBuilder2
{
    public function __construct(
        private readonly IQueryBuilder2 $inner,
        private readonly QueryCounter $counter = new QueryCounter(),
    ) {
    }

    public function selects(): int
    {
        return $this->counter->selects;
    }

    public function inserts(): int
    {
        return $this->counter->inserts;
    }

    public function execs(): int
    {
        return $this->counter->execs;
    }

    public function newQuery(): self
    {
        return new self($this->inner->newQuery(), $this->counter);
    }

    public function select(string $columns = '*'): self
    {
        $this->inner->select($columns);
        return $this;
    }

    public function from(string $table): self
    {
        $this->inner->from($table);
        return $this;
    }

    public function where(string $column, QueryBuilderOperator $operator, mixed $value): self
    {
        $this->inner->where($column, $operator, $value);
        return $this;
    }

    public function orWhere(string $column, QueryBuilderOperator $operator, mixed $value): self
    {
        $this->inner->orWhere($column, $operator, $value);
        return $this;
    }

    public function page(int $limit, int $offset): self
    {
        $this->inner->page($limit, $offset);
        return $this;
    }

    public function sort(string $field, QueryBuilderSortType $direction = QueryBuilderSortType::Ascending): self
    {
        $this->inner->sort($field, $direction);
        return $this;
    }

    public function insert(string $table, array $data): int
    {
        $this->counter->inserts++;
        return $this->inner->insert($table, $data);
    }

    public function update(string $table, array $data): self
    {
        $this->inner->update($table, $data);
        return $this;
    }

    public function delete(string $table): self
    {
        $this->inner->delete($table);
        return $this;
    }

    public function get(): array
    {
        $this->counter->selects++;
        return $this->inner->get();
    }

    public function exec(): int
    {
        $this->counter->execs++;
        return $this->inner->exec();
    }
}

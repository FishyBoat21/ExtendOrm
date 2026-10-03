<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder2;

interface IQueryBuilder2 {
    /**
     * A fresh builder bound to the same connection and dialect.
     *
     * Models derive one of these per query so they never mutate the builder the
     * caller handed them.
     */
    public function newQuery(): self;
    public function select(string $columns = '*'): self;
    public function from(string $table): self;
    public function where(string $column, QueryBuilderOperator $operator, mixed $value): self;
    /** Same as where(), but the condition is joined with OR. */
    public function orWhere(string $column, QueryBuilderOperator $operator, mixed $value): self;
    public function page(int $limit, int $offset): self;
    public function insert(string $table, array $data): int; // Returns lastInsertId
    public function update(string $table, array $data): self; // Chain ->where()->exec()
    public function delete(string $table): self; // Chain ->where()->exec()
    public function get(): array; // Executes SELECT
    public function exec(): int;
    public function sort(string $field, QueryBuilderSortType $direction = QueryBuilderSortType::Ascending): self;
}

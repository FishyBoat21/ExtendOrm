<?php 
namespace FishyBoat21\ExtendOrm\QueryBuilder2;

use PDO;

interface IQueryBuilder2 {
    public function select(string $columns = '*'): self;
    public function from(string $table): self;
    public function where(string $column, QueryBuilderOperator $operator, mixed $value): self;
    public function page(int $limit, int $offset): self;
    public function insert(string $table, array $data): int; // Returns lastInsertId
    public function update(string $table, array $data): self; // Returns rowCount
    public function delete(string $table): self; // Returns rowCount
    public function get(): array; // Executes SELECT
    public function exec(): int;
    public function sort(string $field, QueryBuilderSortType $direction = QueryBuilderSortType::Ascending): self;
}

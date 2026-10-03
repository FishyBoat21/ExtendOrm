<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder2;

use FishyBoat21\ExtendOrm\Dialect\Dialect;
use FishyBoat21\ExtendOrm\Dialect\DialectFactory;
use FishyBoat21\ExtendOrm\ExtendORMException;
use PDO;
use PDOException;

class QueryBuilder2 implements IQueryBuilder2 {
    private array $QueryStringBlock;
    private PDO $db;
    private Dialect $Dialect;

    public function __construct(PDO $db, ?Dialect $dialect = null)
    {
        $this->db = $db;
        $this->Dialect = $dialect ?? DialectFactory::forConnection($db);
        $this->reset();
    }

    /**
     * A fresh builder over the same connection and dialect.
     *
     * Use this instead of reusing one builder for interleaved work: the builder
     * holds the half-built statement, so reusing it mid-chain discards it.
     */
    public function newQuery(): self
    {
        return new self($this->db, $this->Dialect);
    }

    /**
     * Resets the query state to allow object reuse.
     */
    private function reset(): void
    {
        $this->QueryStringBlock = [
            'type'   => '',      // SELECT, INSERT, UPDATE, DELETE
            'table'  => '',
            'columns'=> '',
            'values' => [],      // For INSERT/UPDATE
            'wheres' => [],      // list of ['boolean' => 'AND'|'OR', 'sql' => string, 'params' => array]
            'params' => [],      // Bind parameters for the statement body
            'limit' => null,
            'offset' => null,
            'sorts' => []       // Array of sort strings
        ];
    }

    /**
     * Quotes a comma-separated identifier list ("id, username, created_at").
     */
    private function quoteList(string $identifiers): string
    {
        $quoted = [];
        foreach (explode(',', $identifiers) as $identifier) {
            $identifier = trim($identifier);
            if ($identifier === '') {
                continue;
            }
            $quoted[] = $this->Dialect->quoteIdentifier($identifier);
        }
        if ($quoted === []) {
            throw new ExtendORMException("Cannot build a query with no columns.");
        }
        return implode(', ', $quoted);
    }

    /**
     * Prepare a SELECT statement.
     */
    public function select(string $columns = '*'): self
    {
        $this->reset();
        $this->QueryStringBlock['type'] = 'SELECT';
        $this->QueryStringBlock['columns'] = $columns;
        return $this;
    }

    /**
     * Set the table target.
     */
    public function from(string $table): self
    {
        $this->QueryStringBlock['table'] = $table;
        return $this;
    }

    /**
     * Add a WHERE clause joined with AND.
     * usage: ->where('age', QueryBuilderOperator::MoreThan, 18)
     */
    public function where(string $column, QueryBuilderOperator $operator, mixed $value): self
    {
        return $this->addWhere('AND', $column, $operator, $value);
    }

    /**
     * Add a WHERE clause joined with OR.
     *
     * Note that SQL precedence applies: `a AND b OR c` groups as `(a AND b) OR c`.
     */
    public function orWhere(string $column, QueryBuilderOperator $operator, mixed $value): self
    {
        return $this->addWhere('OR', $column, $operator, $value);
    }

    private function addWhere(string $boolean, string $column, QueryBuilderOperator $operator, mixed $value): self
    {
        [$sql, $params] = $this->compileCondition($column, $operator, $value);
        $this->QueryStringBlock['wheres'][] = [
            'boolean' => $boolean,
            'sql' => $sql,
            'params' => $params,
        ];
        return $this;
    }

    /**
     * Turns one (column, operator, value) triple into SQL plus bind parameters.
     *
     * @return array{0: string, 1: array}
     */
    private function compileCondition(string $column, QueryBuilderOperator $operator, mixed $value): array
    {
        $quoted = $this->Dialect->quoteIdentifier($column);

        if ($operator === QueryBuilderOperator::IsNull || $operator === QueryBuilderOperator::IsNotNull) {
            return ["$quoted $operator->value", []];
        }

        if ($operator === QueryBuilderOperator::Is) {
            if ($value !== null) {
                throw new ExtendORMException(
                    "QueryBuilderOperator::Is only supports NULL. Use IsNull / IsNotNull for NULL checks, " .
                    "or a comparison operator such as Equals for values."
                );
            }
            return ["$quoted IS NULL", []];
        }

        if ($operator === QueryBuilderOperator::In || $operator === QueryBuilderOperator::NotIn) {
            if (!is_array($value) || $value === []) {
                throw new ExtendORMException(
                    "QueryBuilderOperator::{$operator->name} expects a non-empty array of values."
                );
            }
            $values = array_values($value);
            $placeholders = implode(', ', array_fill(0, count($values), '?'));
            return ["$quoted $operator->value ($placeholders)", $values];
        }

        if ($operator === QueryBuilderOperator::Between || $operator === QueryBuilderOperator::NotBetween) {
            if (!is_array($value) || count($value) !== 2) {
                throw new ExtendORMException(
                    "QueryBuilderOperator::{$operator->name} expects a two-element array: [minimum, maximum]."
                );
            }
            $values = array_values($value);
            return ["$quoted $operator->value ? AND ?", $values];
        }

        return ["$quoted $operator->value ?", [$value]];
    }

    /**
     * Prepare and Execute an INSERT statement immediately.
     * Returns the Last Insert ID.
     */
    public function insert(string $table, array $data): int
    {
        $this->reset();
        $this->QueryStringBlock['type'] = 'INSERT';
        $this->QueryStringBlock['table'] = $table;

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $this->QueryStringBlock['columns'] = implode(', ', $columns);
        $this->QueryStringBlock['values'] = implode(', ', $placeholders);
        $this->QueryStringBlock['params'] = array_values($data);

        $this->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Prepare an UPDATE statement.
     * Usage: $qb->update(...)->where(...)->exec();
     */
    public function update(string $table, array $data): self
    {
        $this->reset(); // clear previous query data
        $this->QueryStringBlock['type'] = 'UPDATE';
        $this->QueryStringBlock['table'] = $table;

        $sets = [];
        foreach ($data as $column => $value) {
            $sets[] = $this->Dialect->quoteIdentifier($column) . " = ?";
            $this->QueryStringBlock['params'][] = $value;
        }
        $this->QueryStringBlock['columns'] = implode(', ', $sets);

        return $this; // Usage: $qb->update(...)->where(...)->exec();
    }

    /**
     * Prepare a DELETE statement.
     */
    public function delete(string $table): self
    {
        $this->reset();
        $this->QueryStringBlock['type'] = 'DELETE';
        $this->QueryStringBlock['table'] = $table;
        return $this;
    }

    /**
     * Compiles and executes the query for SELECT queries.
     * Returns Associative Array.
     */
    public function get(): array
    {
        if ($this->QueryStringBlock['type'] !== 'SELECT') {
            // Should be used for SELECT. For others, use exec().
            // Fallback just in case.
            return [];
        }

        $stmt = $this->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->reset(); // Clean up for next query
        return $result;
    }

    /**
     * Final trigger for Update/Delete queries.
     * Returns number of affected rows.
     */
    public function exec(): int
    {
        $stmt = $this->execute();
        $count = $stmt->rowCount();
        $this->reset();
        return $count;
    }

    /**
     * Internal query compiler and executor.
     */
    private function execute()
    {
        $sql = '';
        $type = $this->QueryStringBlock['type'];
        $table = $this->QueryStringBlock['table'];
        $conditions = $this->QueryStringBlock['wheres'];
        $sorts = $this->QueryStringBlock['sorts'];
        $limit = $this->QueryStringBlock['limit'];
        $offset = $this->QueryStringBlock['offset'];

        // Parameters queued by INSERT values / UPDATE assignments.
        $params = $this->QueryStringBlock['params'];

        // Build SQL String
        switch ($type) {
            case 'SELECT':
                $sql = "SELECT " . $this->quoteList($this->QueryStringBlock['columns']) . " FROM " . $this->Dialect->quoteIdentifier($table);
                break;
            case 'INSERT':
                $cols = $this->quoteList($this->QueryStringBlock['columns']);
                $vals = $this->QueryStringBlock['values'];
                $sql = "INSERT INTO " . $this->Dialect->quoteIdentifier($table) . " ($cols) VALUES ($vals)";
                break;
            case 'UPDATE':
                $cols = $this->QueryStringBlock['columns']; // These are actually "col = ?" strings
                $sql = "UPDATE " . $this->Dialect->quoteIdentifier($table) . " SET $cols";
                break;
            case 'DELETE':
                $sql = "DELETE FROM " . $this->Dialect->quoteIdentifier($table);
                break;
            default:
                throw new ExtendORMException("Invalid Query Type");
        }

        // Append WHERE clauses (if any). The first condition never carries a connector.
        if ($conditions !== [] && $type !== 'INSERT') {
            $parts = [];
            foreach ($conditions as $index => $node) {
                $parts[] = ($index === 0 ? '' : $node['boolean'] . ' ') . $node['sql'];
                foreach ($node['params'] as $param) {
                    $params[] = $param;
                }
            }
            $sql .= " WHERE " . implode(' ', $parts);
        }

        // Append ordering and paging
        if($type === 'SELECT'){
            if ($sorts !== []) {
                $sql .= " ORDER BY " . implode(', ', $sorts);
            }
            $sql .= $this->Dialect->limitClause($limit, $offset);
        }

        // Prepare and Execute
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log the failing statement server-side; never embed the SQL text in
            // the exception, which callers may render to end users.
            error_log(sprintf('ExtendOrm query failed: %s | SQL: %s', $e->getMessage(), $sql));
            throw $e;
        }
    }

    /**
     * Set limit and offset for paging
     */
    public function page(int $limit, int $offset):self
    {
        $this->QueryStringBlock['limit'] = $limit;
        $this->QueryStringBlock['offset'] = $offset;
        return $this;
    }

    public function sort(string $field, QueryBuilderSortType $direction = QueryBuilderSortType::Ascending): self
    {
        $type = $this->QueryStringBlock['type'];
        if($type !== 'SELECT'){
            throw new PDOException("Sorting is only applicable to SELECT queries.");
        }
        if (!isset($this->QueryStringBlock['sorts'])) {
            $this->QueryStringBlock['sorts'] = [];
        }
        $this->QueryStringBlock['sorts'][] = $this->Dialect->quoteIdentifier($field) . " $direction->value";
        return $this;
    }

    /** The dialect this builder renders with. */
    public function dialect(): Dialect
    {
        return $this->Dialect;
    }
}

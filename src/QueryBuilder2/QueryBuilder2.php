<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder2;

use PDO;
use PDOException;

class QueryBuilder2 implements IQueryBuilder2 {
    private array $QueryStringBlock;
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->reset();
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
            'wheres' => [],      // Array of condition strings
            'params' => [],      // Bind parameters for PDO
            'limit' => 99999999,
            'offset' => 0,
            'sorts' => []       // Array of sort strings
        ];
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
     * Add a WHERE clause.
     * usage: ->where('age', '>', 18)
     */
    public function where(string $column, QueryBuilderOperator $operator, mixed $value): self
    {
        // specific placeholder to handle multiple wheres safely
        $this->QueryStringBlock['wheres'][] = "$column $operator->value ?";
        $this->QueryStringBlock['params'][] = $value;
        return $this;
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

        $stmt = $this->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Prepare an UPDATE statement.
     * Call ->where() after this, then ->exec() or implement immediate execution logic.
     * Here we prepare the block, but return self to allow chaining ->where().
     * To execute, we will need a finish method.
     */
    public function update(string $table, array $data): self
    {
        $this->reset(); // clear previous query data
        $this->QueryStringBlock['type'] = 'UPDATE';
        $this->QueryStringBlock['table'] = $table;

        $sets = [];
        foreach ($data as $column => $value) {
            $sets[] = "$column = ?";
            $this->QueryStringBlock['params'][] = $value;
        }
        $this->QueryStringBlock['columns'] = implode(', ', $sets);

        // We return an object that can execute, but since this specific method signature
        // usually implies immediate execution in simple builders, we need to handle the WHERE clause.
        // However, standard builders separate `update` and `where`. 
        // *Workaround:* If you need immediate execution without where, we run it here.
        // But to support WHERE, we usually return $this and call a final 'execute' method.
        // For this specific request, I will return 0 here and assume the user calls a finalize method 
        // OR I will interpret this request as "Start the update chain". 
        
        // *Correction based on "Execute Directly" requirement:* // Since `update` usually requires a `where`, we cannot execute immediately inside this function 
        // unless we pass conditions into it. 
        // To keep it simple: This creates the update block. You must call `exec()` or `get()` logic to run it.
        // But strict CRUD methods usually return result. I will create a helper `exec()` method.
        
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
        $wheres = $this->QueryStringBlock['wheres'];
        $sort = $this->QueryStringBlock['sorts'];
        $limit = $this->QueryStringBlock['limit'];
        $offset = $this->QueryStringBlock['offset'];
        // Build SQL String
        switch ($type) {
            case 'SELECT':
                $cols = $this->QueryStringBlock['columns'];
                $sql = "SELECT $cols FROM $table";
                break;
            case 'INSERT':
                $cols = $this->QueryStringBlock['columns'];
                $vals = $this->QueryStringBlock['values'];
                $sql = "INSERT INTO $table ($cols) VALUES ($vals)";
                break;
            case 'UPDATE':
                $cols = $this->QueryStringBlock['columns']; // These are actually "col = ?" strings
                $sql = "UPDATE $table SET $cols";
                break;
            case 'DELETE':
                $sql = "DELETE FROM $table";
                break;
            default:
                throw new PDOException("Invalid Query Type");
        }

        // Append WHERE clauses (if any)
        if (!empty($wheres) && $type !== 'INSERT') {
            $sql .= " WHERE " . implode(' AND ', $wheres);
        }
        // Append paging
        if($type === 'SELECT'){
            if (!empty($sort)) {
                $sql .= " ORDER BY " . implode(', ', $sort);
            }
            if($limit !== null){
                $sql .= " LIMIT $offset, $limit";
            }
        }
        // Prepare and Execute
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($this->QueryStringBlock['params']);
            return $stmt;
        } catch (PDOException $e) {
            throw new PDOException("Query Failed: " . $e->getMessage() . " | SQL: " . $sql);
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
        $this->QueryStringBlock['sorts'][] = "$field $direction->value";
        return $this;
    }
}
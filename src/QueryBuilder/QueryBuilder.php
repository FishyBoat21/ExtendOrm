<?php
namespace Kevin1358\ExtendOrm\QueryBuilder;
use Kevin1358\ExtendOrm\QueryBuilder\Query;
use Kevin1358\ExtendOrm\QueryBuilder\Joinable;
use Kevin1358\ExtendOrm\QueryBuilder\Set;
use Kevin1358\ExtendOrm\QueryBuilder\Queryable;

class QueryBuilder {
    public Query $queryObj;

    public function __construct(Query $queryObj)
    {
        $this->queryObj = $queryObj;
    }
    public static function insert(string $table, array $fields, array $values)
    {
        $queryObj = new Query();
        $placeholders = array_fill(0, count($fields), '?');
        $queryObj->query = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $queryObj->values = $values;
        return new Queryable($queryObj);
    }
    public static function select(array $fields = ['*'], string $table):Joinable
    {
        $queryObj = new Query();
        $queryObj->query = "SELECT ".implode(', ', $fields)." FROM $table";
        return new Joinable($queryObj);
    }
    public static function update(string $table):Set
    {
        $queryObj = new Query();
        $queryObj->query = "UPDATE $table";
        return new Set($queryObj);
    }
    public static function delete(string $table):Queryable
    {
        $queryObj = new Query();
        $queryObj->query = "DELETE FROM $table";
        return new Queryable($queryObj);
    }
}
?>
<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder;

use FishyBoat21\ExtendOrm\QueryBuilder\Query;
use FishyBoat21\ExtendOrm\QueryBuilder\Joinable;
use FishyBoat21\ExtendOrm\QueryBuilder\Set;
use FishyBoat21\ExtendOrm\QueryBuilder\Queryable;

class QueryBuilder {
    public Query $queryObj;

    public function __construct(Query $queryObj)
    {
        $this->queryObj = $queryObj;
    }
    public static function insert(string $table, array $fields, array $values):Queryable
    {
        $queryBlock = new Block;
        $placeholders = array_fill(0, count($fields), '?');
        $queryBlock->query = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $queryBlock->values = $values;
        $queryObj = new Query;
        $queryObj->blockA = $queryBlock;
        return new Queryable($queryObj);
    }
    public static function select(array $fields = ['*'], string $table):Joinable
    {
        $queryBlock = new Block;
        $queryBlock->query = "SELECT ".implode(', ', $fields)." FROM $table";
        $queryObj = new Query();
        $queryObj->blockA = $queryBlock;
        return new Joinable($queryObj);
    }
    public static function update(string $table):Set
    {
        $queryBlock = new Block;
        $queryBlock->query = "UPDATE $table";
        $queryObj = new Query();
        $queryObj->blockA = $queryBlock;
        return new Set($queryObj);
    }
    public static function delete(string $table):Where
    {
        $queryBlock = new Block();
        $queryBlock->query = "DELETE FROM $table";
        $queryObj = new Query();
        $queryObj->blockA = $queryBlock;
        return new Where($queryObj);
    }
}
?>
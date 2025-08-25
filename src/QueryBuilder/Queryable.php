<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder;

use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilder;
use FishyBoat21\ExtendOrm\Database;
use PDOStatement;

class Queryable extends QueryBuilder{
    public function limit(int $limit, int $offset):self{
        $queryBlock = new Block();
        $queryBlock->query = " LIMIT $offset,$limit";
        $queryBlock->values = [];
        $this->queryObj->blockE = $queryBlock;
        return $this;
    }
    public function query():PDOStatement{
        $conn = Database::getInstance()->getConnection();
        $queryString = "";
        $values = [];
        foreach($this->queryObj as $block){
            if($block == null) continue;
            $queryString .= $block->query;
            $values = array_merge($values, $block->values);  
        }
        $stmt = $conn->prepare($queryString);
        $stmt->execute($values);
        return $stmt;
    }
}
?>
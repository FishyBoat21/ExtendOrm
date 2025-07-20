<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder;

use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilder;
use FishyBoat21\ExtendOrm\Database;
use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilderOperator;

class Queryable extends QueryBuilder{
    
    public function where(string $field, QueryBuilderOperator $operator, $value):self {
        $this->queryObj->query .= (strpos($this->queryObj->query, 'WHERE') === false) ? " WHERE $field $operator->value ?" : " AND $field $operator->value ?";
        $this->queryObj->values[] = $value;
        return $this;
    }

    public function orWhere(string $field, QueryBuilderOperator $operator, $value):self {
        $this->queryObj->query .= " OR $field $operator->value ?";
        $this->queryObj->values[] = $value;
        return $this;
    }
    public function query(){
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare($this->queryObj->query);
        $stmt->execute($this->queryObj->values);
        return $stmt;
    }
}
?>
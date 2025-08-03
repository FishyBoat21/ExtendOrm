<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder;
use FishyBoat21\ExtendOrm\QueryBuilder\Queryable;

class Where extends Queryable{
    public function where(string $field, QueryBuilderOperator $operator, $value):self {
        if($this->queryObj->blockD == null) $this->queryObj->blockD = new Block();
        $this->queryObj->blockD->query .= (strpos($this->queryObj->blockD->query, 'WHERE') === false) ? " WHERE $field $operator->value ?" : " AND $field $operator->value ?";
        $this->queryObj->blockD->values[] = $value;
        return $this;
    }

    public function orWhere(string $field, QueryBuilderOperator $operator, $value):self {
        $this->queryObj->blockD->query .= " OR $field $operator->value ?";
        $this->queryObj->blockD->values[] = $value;
        return $this;
    }
}
?>
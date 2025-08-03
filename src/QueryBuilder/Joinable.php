<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder;

use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilderJoinType;
use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilderOperator;

class Joinable extends Where{
    public function join(string $table, string $localColumn, QueryBuilderOperator $operator, string $foreignColumn, QueryBuilderJoinType $joinType = QueryBuilderJoinType::Inner): self {
        if($this->queryObj->blockC == null) $this->queryObj->blockC = new Block();
        $this->queryObj->blockC->query .= " $joinType->value JOIN $table ON $localColumn $operator->value $foreignColumn";
        return $this;
    }
}
?>
<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder;

use FishyBoat21\ExtendOrm\QueryBuilder\Queryable;
use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilderJoinType;
use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilderOperator;

class Joinable extends Queryable{
    public function join(string $table, string $localColumn, QueryBuilderOperator $operator, string $foreignColumn, QueryBuilderJoinType $joinType = QueryBuilderJoinType::Inner): self {
        $this->queryObj->query .= " $joinType->value JOIN $table ON $localColumn $operator->value $foreignColumn";
        return $this;
    }
}
?>
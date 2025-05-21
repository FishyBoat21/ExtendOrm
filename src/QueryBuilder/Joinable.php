<?php
namespace Kevin1358\ExtendOrm\QueryBuilder;

use Kevin1358\ExtendOrm\QueryBuilder\Queryable;
use Kevin1358\ExtendOrm\QueryBuilder\QueryBuilderJoinType;
use Kevin1358\ExtendOrm\QueryBuilder\QueryBuilderOperator;

class Joinable extends Queryable{
    public function join(string $table, string $localColumn, QueryBuilderOperator $operator, string $foreignColumn, QueryBuilderJoinType $joinType = QueryBuilderJoinType::Inner): self {
        $this->queryObj->query .= " $joinType->value JOIN $table ON $localColumn $operator->value $foreignColumn";
        return $this;
    }
}
?>
<?php
namespace FishyBoat21\ExtendOrm;

use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderSortType;

class Sort{
    public string $Field;
    public QueryBuilderSortType $Direction;
    public function __construct(string $field, QueryBuilderSortType $direction = QueryBuilderSortType::Ascending){
        $this->Field = $field;
        $this->Direction = $direction;
    }
}
<?php
namespace FishyBoat21\ExtendOrm;

use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilderOperator;

class Criterion{
    public QueryBuilderOperator $Operator;
    public $Value;
    public $Key;
    public function __construct(QueryBuilderOperator $operator,$value) {
        $this->Operator = $operator;
        $this->Value = $value;
    }
}
?>
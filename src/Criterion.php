<?php
namespace FishyBoat21\ExtendOrm;

use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilderOperator;

class Criterion{
    public QueryBuilderOperator $Operator;
    public $Value;
    public string $Key;
    public function __construct(string $key, QueryBuilderOperator $operator,$value) {
        $this->Key = $key;
        $this->Operator = $operator;
        $this->Value = $value;
    }
}
?>
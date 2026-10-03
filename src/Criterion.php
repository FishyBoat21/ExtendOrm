<?php
namespace FishyBoat21\ExtendOrm;

use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;

class Criterion{
    public QueryBuilderOperator $Operator;
    public $Value;
    public string $Key;
    /** How this condition joins the previous one: 'AND' or 'OR'. */
    public string $Boolean = 'AND';
    public function __construct(string $key, QueryBuilderOperator $operator,$value, string $boolean = 'AND') {
        $this->Key = $key;
        $this->Operator = $operator;
        $this->Value = $value;
        $this->Boolean = $boolean;
    }
}

<?php
namespace Kevin1358\ExtendOrm\QueryBuilder;

use Kevin1358\ExtendOrm\QueryBuilder\QueryBuilder;

class Set extends QueryBuilder{
    public function set(array $fields, array $values):Queryable {
        $this->queryObj->query .= " SET";
        $placeholders =[];
        foreach($fields as $field){
            $placeholders[] = " $field = ?";
        }
        $this->queryObj->query .= implode(",",$placeholders);
        $this->queryObj->values += $values;
        return new Queryable($this->queryObj);
    }
}
?>
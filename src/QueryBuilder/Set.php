<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder;

use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilder;

class Set extends QueryBuilder{
    public function set(array $fields, array $values):Where {
        $queryBlock = new Block;
        $queryBlock->query = " SET";
        $placeholders =[];
        foreach($fields as $field){
            $placeholders[] = " $field = ?";
        }
        $queryBlock->query .= implode(",",$placeholders);
        $queryBlock->values = $values;
        $this->queryObj->blockC = $queryBlock;
        return new Where($this->queryObj);
    }
}
?>
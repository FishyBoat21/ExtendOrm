<?php
namespace FishyBoat21\ExtendOrm;
class Criteria{
    public array $Criterion = [];
    public array $Sort = [];
    /** Relation names to preload for the whole result set. */
    public array $With = [];
    public function Add(Criterion $criterion){
        $this->Criterion[] = $criterion;
        return $this;
    }
    /**
     * Adds a condition joined with OR.
     *
     * SQL precedence applies, so `a AND b OR c` reads as `(a AND b) OR c`.
     */
    public function AddOr(Criterion $criterion){
        $criterion->Boolean = 'OR';
        $this->Criterion[] = $criterion;
        return $this;
    }
    public function AddSort(Sort $sort){
        $this->Sort[] = $sort;
        return $this;
    }
    /**
     * Preloads the named relations in one query each instead of querying per
     * model as the relations are touched.
     */
    public function AddWith(string ...$relations){
        foreach ($relations as $relation) {
            if (!in_array($relation, $this->With, true)) {
                $this->With[] = $relation;
            }
        }
        return $this;
    }
}

<?php
namespace FishyBoat21\ExtendOrm;
class Criteria{
    public array $Criterion = [];
    public array $Sort = [];
    public function Add(Criterion $criterion){
        $this->Criterion[] = $criterion;
        return $this;
    }
    public function AddSort(Sort $sort){
        $this->Sort[] = $sort;
        return $this;
    }
}
?>
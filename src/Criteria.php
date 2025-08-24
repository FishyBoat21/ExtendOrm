<?php
namespace FishyBoat21\ExtendOrm;
class Criteria{
    public array $Criterion;
    public function Add(Criterion $criterion){
        $this->Criterion[] = $criterion;
        return $this;
    }
}
?>
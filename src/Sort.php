<?php
namespace FishyBoat21\ExtendOrm;
class Sort{
    public string $Field;
    public string $Direction;
    public function __construct(string $field, string $direction = "ASC"){
        $this->Field = $field;
        $this->Direction = $direction;
    }
}
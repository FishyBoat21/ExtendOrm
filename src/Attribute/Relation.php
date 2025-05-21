<?php
namespace Kevin1358\ExtendOrm\Attribute;
use Attribute;

#[Attribute()]
class Relation{
    public string $targetModel;
    public string $objAlias;
    public function __construct(string $targetModel, string $objAlias) {
        $this->targetModel = $targetModel;
        $this->objAlias = $objAlias;
    }
}
?>
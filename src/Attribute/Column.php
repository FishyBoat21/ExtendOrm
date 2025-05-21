<?php
namespace Kevin1358\ExtendOrm\Attribute;
use Attribute;

#[Attribute()]
class Column{
    public string $column;
    public function __construct(string $column) {
        $this->column = $column;
    }
}

?>
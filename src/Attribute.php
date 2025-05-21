<?php
namespace ExtendORM;

use Attribute;

#[Attribute()]
class Table{
    public string $table;
    public function __construct(string $table) {
        $this->table = $table;
    }
}

#[Attribute()]
class Column{
    public string $column;
    public function __construct(string $column) {
        $this->column = $column;
    }
}

#[Attribute()]
class PrimaryKey{}

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
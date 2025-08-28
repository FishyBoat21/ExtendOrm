<?php
namespace FishyBoat21\ExtendOrm\Attribute;
use Attribute;
use FishyBoat21\ExtendOrm\Attribute\Relation\RelationType;

#[Attribute()]
class Relation{
    public RelationType $type;
    public string $target;
    public string $foreignKey;
    public string $localKey;
    public string $ownerKey;
    public function __construct(?RelationType $type,?string $target, ?string $foreignKey,?string $localKey, ?string $ownerKey) {
        $this->type = $type;
        $this->target = $target;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->ownerKey = $ownerKey;
    }
}
?>
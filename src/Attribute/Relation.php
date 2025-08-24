<?php
namespace FishyBoat21\ExtendOrm\Attribute;
use Attribute;

#[Attribute()]
class Relation{
    public static string $HasMany = "hasMany";
    public static string $BelongsTo = "belongsTo";
    public string $type;
    public string $target;
    public string $foreignKey;
    public string $localKey;
    public string $ownerKey;
    public function __construct(?string $type,?string $target, ?string $foreignKey,?string $localKey, ?string $ownerKey) {
        $this->type = $type;
        $this->target = $target;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->ownerKey = $ownerKey;
    }
}
?>
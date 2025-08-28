<?php
namespace FishyBoat21\ExtendOrm\Attribute\Relation;

enum RelationType{
    case HasOne;
    case HasMany;
    case BelongsTo;
}

?>
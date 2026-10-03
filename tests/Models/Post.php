<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests\Models;

use FishyBoat21\ExtendOrm\Attribute\Column;
use FishyBoat21\ExtendOrm\Attribute\PrimaryKey;
use FishyBoat21\ExtendOrm\Attribute\Relation;
use FishyBoat21\ExtendOrm\Attribute\Relation\RelationType;
use FishyBoat21\ExtendOrm\Attribute\Table;
use FishyBoat21\ExtendOrm\Model;

#[Table('posts')]
class Post extends Model
{
    #[PrimaryKey]
    #[Column('id')]
    public int $id;

    /** Property name intentionally differs from its column name. */
    #[Column('user_id')]
    public int $userId;

    #[Column('title')]
    public string $title;

    /** Column name is a SQL reserved word. */
    #[Column('order')]
    public int $order = 0;

    #[Relation(
        type: RelationType::BelongsTo,
        target: User::class,
        foreignKey: 'user_id',
        localKey: 'user_id',
        ownerKey: 'id'
    )]
    public ?User $author = null;
}

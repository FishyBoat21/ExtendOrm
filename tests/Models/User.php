<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests\Models;

use FishyBoat21\ExtendOrm\Attribute\Column;
use FishyBoat21\ExtendOrm\Attribute\PrimaryKey;
use FishyBoat21\ExtendOrm\Attribute\Relation;
use FishyBoat21\ExtendOrm\Attribute\Relation\RelationType;
use FishyBoat21\ExtendOrm\Attribute\Table;
use FishyBoat21\ExtendOrm\Model;

#[Table('users')]
class User extends Model
{
    #[PrimaryKey]
    #[Column('id')]
    public int $id;

    #[Column('username')]
    public string $username;

    #[Column('email')]
    public ?string $email = null;

    /** Property name intentionally differs from its column name. */
    #[Column('created_at')]
    public ?string $createdAt = null;

    #[Relation(
        type: RelationType::HasMany,
        target: Post::class,
        foreignKey: 'user_id',
        localKey: 'id',
        ownerKey: 'id'
    )]
    public array $posts = [];

    #[Relation(
        type: RelationType::HasOne,
        target: Profile::class,
        foreignKey: 'user_id',
        localKey: 'id',
        ownerKey: 'id'
    )]
    public ?Profile $profile = null;
}

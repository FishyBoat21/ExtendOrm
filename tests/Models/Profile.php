<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests\Models;

use FishyBoat21\ExtendOrm\Attribute\Column;
use FishyBoat21\ExtendOrm\Attribute\PrimaryKey;
use FishyBoat21\ExtendOrm\Attribute\Table;
use FishyBoat21\ExtendOrm\Model;

#[Table('profiles')]
class Profile extends Model
{
    #[PrimaryKey]
    #[Column('id')]
    public int $id;

    #[Column('user_id')]
    public int $userId;

    #[Column('phone')]
    public string $phone;
}

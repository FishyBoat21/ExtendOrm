<?php
declare(strict_types=1);

namespace FishyBoat21\ExtendOrm\Tests;

use FishyBoat21\ExtendOrm\Attribute\Column;
use FishyBoat21\ExtendOrm\Attribute\PrimaryKey;
use FishyBoat21\ExtendOrm\Attribute\Relation;
use FishyBoat21\ExtendOrm\Attribute\Relation\RelationType;
use FishyBoat21\ExtendOrm\Attribute\Table;
use FishyBoat21\ExtendOrm\Criteria;
use FishyBoat21\ExtendOrm\ExtendORMException;
use FishyBoat21\ExtendOrm\Model;
use FishyBoat21\ExtendOrm\ModelMap;
use FishyBoat21\ExtendOrm\Tests\Models\Post;
use FishyBoat21\ExtendOrm\Tests\Models\User;
use ReflectionProperty;

final class AttributeMappingTest extends TestCase
{
    public function testTableNameComesFromAttribute(): void
    {
        $this->assertSame('users', User::GetTableName());
        $this->assertSame('posts', Post::GetTableName());
    }

    public function testModelMapHoldsBothColumnDirections(): void
    {
        $map = self::mapFor(User::class);

        $this->assertSame('id', $map->PrimaryKey);
        $this->assertSame('users', $map->Table);
        $this->assertSame('username', $map->FieldPropMap['username']);
        $this->assertSame('createdAt', $map->FieldPropMap['created_at']);
        $this->assertSame('created_at', $map->PropColumnMap['createdAt']);
    }

    public function testModelMapHoldsRelations(): void
    {
        $map = self::mapFor(User::class);

        $this->assertArrayHasKey('posts', $map->RelationMap);
        $this->assertArrayHasKey('profile', $map->RelationMap);
        $this->assertSame('user_id', $map->RelationMap['posts']['foreignKey']);
    }

    /**
     * Initialize() must run reflection once per class. Before 2.0.0-alpha.2.5 it
     * rebuilt the map on every Find* call.
     */
    public function testReflectionRunsOnlyOncePerModel(): void
    {
        User::GetTableName();
        $before = self::mapFor(User::class);

        User::FindMany(new Criteria(), $this->queryBuilder());

        $this->assertSame($before, self::mapFor(User::class));
    }

    public function testModelWithoutTableAttributeThrows(): void
    {
        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('Table Not Defined');

        TablelessModel::GetTableName();
    }

    public function testModelWithoutPrimaryKeyThrows(): void
    {
        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('Primary key not set');

        KeylessModel::GetTableName();
    }

    /**
     * A primary key that is not also mapped with #[Column] used to collapse into
     * an empty column name and silently emit malformed SQL.
     */
    public function testPrimaryKeyMustAlsoBeMappedToAColumn(): void
    {
        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('must also be mapped with #[Column]');

        UnmappedKeyModel::GetTableName();
    }

    /**
     * A BelongsTo relation needs the target's key; without it the relation would
     * silently query an empty column name.
     */
    public function testBelongsToWithoutOwnerKeyThrows(): void
    {
        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('must declare ownerKey');

        OwnerKeylessRelationModel::GetTableName();
    }

    public function testHasManyWithoutLocalKeyThrows(): void
    {
        $this->expectException(ExtendORMException::class);
        $this->expectExceptionMessage('must declare localKey');

        LocalKeylessRelationModel::GetTableName();
    }

    private static function mapFor(string $class): ModelMap
    {
        $property = new ReflectionProperty(Model::class, 'ModelMap');
        $property->setAccessible(true);
        /** @var array<class-string, ModelMap> $maps */
        $maps = $property->getValue();

        return $maps[$class];
    }
}

class TablelessModel extends Model
{
    #[PrimaryKey]
    #[Column('id')]
    public int $id;
}

#[Table('keyless')]
class KeylessModel extends Model
{
    #[Column('id')]
    public int $id;
}

#[Table('unmapped_key')]
class UnmappedKeyModel extends Model
{
    #[PrimaryKey]
    public int $id;
}

#[Table('owner_keyless')]
class OwnerKeylessRelationModel extends Model
{
    #[PrimaryKey]
    #[Column('id')]
    public int $id;

    #[Column('user_id')]
    public int $userId;

    #[Relation(
        type: RelationType::BelongsTo,
        target: User::class,
        foreignKey: 'user_id',
        localKey: 'user_id',
        ownerKey: null
    )]
    public ?User $author = null;
}

#[Table('local_keyless')]
class LocalKeylessRelationModel extends Model
{
    #[PrimaryKey]
    #[Column('id')]
    public int $id;

    #[Relation(
        type: RelationType::HasMany,
        target: Post::class,
        foreignKey: 'user_id',
        localKey: null,
        ownerKey: 'id'
    )]
    public array $posts = [];
}

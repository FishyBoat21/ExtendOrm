<?php
namespace FishyBoat21\ExtendOrm;

use FishyBoat21\ExtendOrm\Attribute\Column;
use FishyBoat21\ExtendOrm\Attribute\PrimaryKey;
use FishyBoat21\ExtendOrm\Attribute\Relation;
use FishyBoat21\ExtendOrm\Attribute\Relation\RelationType;
use FishyBoat21\ExtendOrm\Attribute\Table;
use FishyBoat21\ExtendOrm\QueryBuilder2\IQueryBuilder2;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;

use ReflectionClass;
use ReflectionProperty;

abstract class Model {
    /** @var array<class-string, ModelMap> */
    protected static array $ModelMap = array();
    protected IQueryBuilder2 $QueryBuilder;
    protected array $loadedRelations = [];
    /** Values as last read from (or written to) the database, keyed by column. */
    protected array $Original = [];

    public function __construct(IQueryBuilder2 $queryBuilder) {
        static::Initialize();
        $this->QueryBuilder = $queryBuilder;
        foreach (array_keys(static::$ModelMap[static::class]->RelationMap) as $relationProp) {
            unset($this->$relationProp);
        }
    }

    /**
     * Builds the cached ModelMap for this model class.
     *
     * Reflection runs once per class; every later call is a no-op. This is what
     * makes repeated FindMany()/Save() calls cheap.
     */
    protected static function Initialize():void {
        if (isset(static::$ModelMap[static::class])) {
            return;
        }

        $map = new ModelMap();
        $refClass = new ReflectionClass(static::class);

        foreach ($refClass->getAttributes() as $attribute) {
            if ($attribute->getName() == Table::class) {
                $map->Table = $attribute->getArguments()[0];
            }
        }
        if ($map->Table === "") {
            throw new ExtendORMException("Table Not Defined");
        }

        $props = $refClass->getProperties();
        foreach($props as $prop){
            $refProp = new ReflectionProperty(static::class,$prop->getName());
            $attributes = $refProp->getAttributes();
            foreach ($attributes as $attribute) {
                if($attribute->getName() == PrimaryKey::class && $map->PrimaryKey == ""){
                    $map->PrimaryKey = $prop->getName();
                }
                if($attribute->getName() == Column::class){
                    $field = $attribute->getArguments()[0];
                    $map->FieldPropMap[$field] = $prop->getName();
                    $map->PropColumnMap[$prop->getName()] = $field;
                }
                if($attribute->getName() == Relation::class){
                    $args = $attribute->getArguments();
                    // Expecting: type, target, foreignKey, localKey/ownerKey
                    $map->RelationMap[$prop->getName()] = [
                        "type" => $args['type'] ?? null,
                        "target" => $args['target'] ?? null,
                        "foreignKey" => $args['foreignKey'] ?? null,
                        "localKey" => $args['localKey'] ?? null,
                        "ownerKey" => $args['ownerKey'] ?? null
                    ];
                }
            }
        }

        if($map->PrimaryKey == ""){
            throw new ExtendORMException("Primary key not set");
        }
        if(!isset($map->PropColumnMap[$map->PrimaryKey])){
            throw new ExtendORMException(sprintf(
                "Primary key '%s' on %s must also be mapped with #[Column].",
                $map->PrimaryKey,
                static::class
            ));
        }
        static::ValidateRelations($map);

        static::$ModelMap[static::class] = $map;
    }

    /**
     * Rejects a relation declaration that cannot work, rather than letting it
     * silently build a query against an empty column name.
     */
    protected static function ValidateRelations(ModelMap $map): void {
        foreach ($map->RelationMap as $property => $relation) {
            if ($relation['type'] === null || $relation['target'] === null || $relation['foreignKey'] === null) {
                throw new ExtendORMException(sprintf(
                    "Relation '%s' on %s must declare type, target and foreignKey.",
                    $property,
                    static::class
                ));
            }
            if ($relation['type'] === RelationType::BelongsTo) {
                if ($relation['ownerKey'] === null) {
                    throw new ExtendORMException(sprintf(
                        "BelongsTo relation '%s' on %s must declare ownerKey.",
                        $property,
                        static::class
                    ));
                }
            } elseif ($relation['localKey'] === null) {
                throw new ExtendORMException(sprintf(
                    "Relation '%s' on %s must declare localKey.",
                    $property,
                    static::class
                ));
            }
        }
    }

    public static function GetTableName(){
        static::Initialize();
        return static::$ModelMap[static::class]->Table;
    }

    /**
     * Translates a criterion/sort key into a database column name.
     *
     * Accepts either the PHP property name (canonical) or the database column
     * name, so criteria written against either keep working. Replaces the
     * previous unguarded array_search(), which silently collapsed an unknown key
     * into an empty column name and produced malformed SQL.
     */
    protected static function ResolveColumn(string $key): string {
        static::Initialize();
        $map = static::$ModelMap[static::class];

        if (array_key_exists($key, $map->PropColumnMap)) {
            return $map->PropColumnMap[$key];
        }
        if (array_key_exists($key, $map->FieldPropMap)) {
            return $key;
        }

        throw new ExtendORMException(sprintf(
            "Unknown property or column '%s' on %s. Mapped properties: [%s]. Mapped columns: [%s].",
            $key,
            static::class,
            implode(', ', array_keys($map->PropColumnMap)),
            implode(', ', array_keys($map->FieldPropMap))
        ));
    }

    /**
     * Reads a model property by either its property name or its column name.
     * Used when resolving relation keys, which are declared as column names.
     */
    protected function ReadKey(string $key): mixed {
        $map = static::$ModelMap[static::class];
        $prop = $map->FieldPropMap[$key] ?? $key;
        return $this->$prop ?? null;
    }

    protected function GetValues():array{
        $values = [];
        foreach(array_values(static::$ModelMap[static::class]->FieldPropMap) as $prop){
            $values[] = $this->$prop ?? null;
        }
        return $values;
    }

    /**
     * Columns whose current value differs from what was last loaded or saved.
     *
     * A model that was never loaded (Original is empty) reports every column, so
     * the hand-built "set the primary key, then save()" pattern still writes a
     * complete row.
     *
     * @return array<string,mixed> column => new value
     */
    protected function ChangedFields(): array {
        $changes = [];
        foreach (static::$ModelMap[static::class]->FieldPropMap as $column => $prop) {
            $value = $this->$prop ?? null;
            if (!array_key_exists($column, $this->Original) || $this->Original[$column] !== $value) {
                $changes[$column] = $value;
            }
        }
        return $changes;
    }

    /** Records the current property values as the new baseline. */
    protected function SyncOriginal(): void {
        $original = [];
        foreach (static::$ModelMap[static::class]->FieldPropMap as $column => $prop) {
            $original[$column] = $this->$prop ?? null;
        }
        $this->Original = $original;
    }

    public function Save() {
        static::Initialize();
        $map = static::$ModelMap[static::class];
        $primaryKey = $map->PrimaryKey;
        $primaryKeyField = $map->PropColumnMap[$primaryKey];

        // isset() is false for both an uninitialized typed property and a null one,
        // which is exactly the "this record was never saved" case.
        if (isset($this->$primaryKey)) {
            // Only write what actually changed, so a stale in-memory copy cannot
            // clobber columns another writer updated in the meantime.
            $changed = $this->ChangedFields();
            if ($changed !== []) {
                $this->QueryBuilder->newQuery()
                ->update(static::GetTableName(),$changed)
                ->where($primaryKeyField,QueryBuilderOperator::Equals,$this->$primaryKey)
                ->exec();
            }
            $this->SyncOriginal();
        } else {
            $data = array_combine(array_keys($map->FieldPropMap), $this->GetValues());
            unset($data[$primaryKeyField]);
            $this->$primaryKey = $this->QueryBuilder->newQuery()
            ->insert(static::GetTableName(),$data);
            $this->SyncOriginal();
        }
        return $this;
    }

    public function Delete():void {
        static::Initialize();
        $map = static::$ModelMap[static::class];
        $primaryKey = $map->PrimaryKey;

        if (!isset($this->$primaryKey)) {
            throw new ExtendORMException("Not a valid record");
        }

        $result = $this->QueryBuilder->newQuery()
        ->delete(static::GetTableName())
        ->where($map->PropColumnMap[$primaryKey],QueryBuilderOperator::Equals,$this->$primaryKey)
        ->exec();

        if ($result !== 1) {
            throw new ExtendORMException("Delete failed or affected multiple rows");
        }

        try {
            $this->$primaryKey = null;
        } catch (\TypeError) {
            unset($this->$primaryKey);
        }
        $this->Original = [];
    }

    public function __get($name)
    {
        if(!isset(static::$ModelMap[static::class])) {
            static::Initialize();
        }
        if(isset(static::$ModelMap[static::class]->RelationMap[$name])){
            if(array_key_exists($name, $this->loadedRelations)){
                return $this->loadedRelations[$name];
            }

            $relation = static::$ModelMap[static::class]->RelationMap[$name];
            $type = $relation["type"];
            $target = $relation["target"];
            $foreignKey = $relation["foreignKey"];
            $localKey = $relation["localKey"] ?? null;
            $ownerKey = $relation["ownerKey"] ?? null;

            $result = null;
            if($type === RelationType::HasMany){
                $localValue = $this->ReadKey($localKey);
                $result = $target::FindMany((new Criteria())->Add(new Criterion($foreignKey,QueryBuilderOperator::Equals,$localValue)), $this->QueryBuilder);
            }
            elseif($type ===  RelationType::BelongsTo){
                $foreignValue = $this->ReadKey($foreignKey);
                $result = $target::FindOne((new Criteria())->Add(new Criterion($ownerKey,QueryBuilderOperator::Equals,$foreignValue)), $this->QueryBuilder);
            }
            elseif($type === RelationType::HasOne){
                $localValue = $this->ReadKey($localKey);
                $result = $target::FindOne((new Criteria())->Add(new Criterion($foreignKey,QueryBuilderOperator::Equals,$localValue)), $this->QueryBuilder);
            }

            $this->loadedRelations[$name] = $result;
            try {
                $this->$name = $result;
            } catch (\TypeError) {
                // Keep in loadedRelations if property doesn't allow the assigned type
            }
            return $result;
        }
        return null;
    }

    /**
     * Applies criteria and sorting onto a fresh SELECT for this model.
     * Shared by FindMany / FindOne / Paging so the translation rules live once.
     */
    protected static function BuildQuery(Criteria $criteria, IQueryBuilder2 $qb): IQueryBuilder2 {
        static::Initialize();
        $map = static::$ModelMap[static::class];
        // Derive a fresh builder so the caller's builder is never mutated.
        $query = $qb->newQuery()
        ->select(implode(",",array_keys($map->FieldPropMap)))
        ->from($map->Table);
        foreach($criteria->Criterion as $criterion){
            $column = static::ResolveColumn($criterion->Key);
            $query = $criterion->Boolean === 'OR'
                ? $query->orWhere($column,$criterion->Operator,$criterion->Value)
                : $query->where($column,$criterion->Operator,$criterion->Value);
        }
        foreach($criteria->Sort as $sort){
            $query = $query->sort(static::ResolveColumn($sort->Field),$sort->Direction);
        }
        return $query;
    }

    /**
     * Turns raw SELECT rows into hydrated model instances.
     */
    protected static function Hydrate(array $rows, IQueryBuilder2 $qb): array {
        $map = static::$ModelMap[static::class];
        $modelType = static::class;
        $resultObj = array();
        foreach($rows as $result){
            $model = new $modelType($qb);
            foreach($result as $key=>$value){
                $prop = $map->FieldPropMap[$key] ?? null;
                if ($prop !== null) {
                    $model->$prop = $value;
                }
            }
            $model->SyncOriginal();
            $resultObj[] = $model;
        }
        return $resultObj;
    }

    /**
     * Preloads the named relations for a whole result set, one query per
     * relation instead of one query per model.
     *
     * @param list<static> $models
     * @param list<string> $relations
     */
    protected static function EagerLoad(array $models, array $relations, IQueryBuilder2 $qb): void {
        if ($models === [] || $relations === []) {
            return;
        }
        $map = static::$ModelMap[static::class];

        foreach ($relations as $name) {
            if (!isset($map->RelationMap[$name])) {
                throw new ExtendORMException(sprintf(
                    "'%s' is not a relation on %s. Known relations: [%s].",
                    $name,
                    static::class,
                    implode(', ', array_keys($map->RelationMap))
                ));
            }

            $relation = $map->RelationMap[$name];
            $type = $relation['type'];
            $target = $relation['target'];
            $foreignKey = $relation['foreignKey'];
            $localKey = $relation['localKey'] ?? null;
            $ownerKey = $relation['ownerKey'] ?? null;

            // BelongsTo matches the target's owner key against this model's foreign
            // key; HasOne/HasMany match the target's foreign key against this
            // model's local key.
            $isBelongsTo = $type === RelationType::BelongsTo;
            $matchOnTarget = $isBelongsTo ? $ownerKey : $foreignKey;
            $readFromParent = $isBelongsTo ? $foreignKey : $localKey;

            $keys = [];
            foreach ($models as $model) {
                $value = $model->ReadKey($readFromParent);
                if ($value !== null) {
                    $keys[(string) $value] = true;
                }
            }

            if ($keys === []) {
                foreach ($models as $model) {
                    $model->loadedRelations[$name] = $type === RelationType::HasMany ? [] : null;
                }
                continue;
            }

            $related = $target::FindMany(
                (new Criteria())->Add(new Criterion($matchOnTarget, QueryBuilderOperator::In, array_keys($keys))),
                $qb
            );

            $grouped = [];
            foreach ($related as $row) {
                $grouped[(string) $row->ReadKey($matchOnTarget)][] = $row;
            }

            foreach ($models as $model) {
                $value = $model->ReadKey($readFromParent);
                $bucket = $value === null ? [] : ($grouped[(string) $value] ?? []);
                $model->loadedRelations[$name] = $type === RelationType::HasMany
                    ? $bucket
                    : ($bucket[0] ?? null);
            }
        }
    }

    public static function FindMany(Criteria $criteria,IQueryBuilder2 $qb):array{
        $query = static::BuildQuery($criteria, $qb);
        $models = static::Hydrate($query->get(), $qb);
        static::EagerLoad($models, $criteria->With, $qb);
        return $models;
    }

    public static function FindOne(Criteria $criteria,IQueryBuilder2 $qb):?static{
        $query = static::BuildQuery($criteria, $qb);
        $results = $query->page(1, 0)->get();
        if(empty($results)){
            return null;
        }
        $hydrated = static::Hydrate(array($results[0]), $qb);
        if ($hydrated === []) {
            return null;
        }
        static::EagerLoad($hydrated, $criteria->With, $qb);
        return $hydrated[0];
    }

    public static function Paging(int $limit,int $offset,Criteria $criteria,IQueryBuilder2 $qb):array{
        $query = static::BuildQuery($criteria, $qb);
        $models = static::Hydrate($query->page($limit,$offset)->get(), $qb);
        static::EagerLoad($models, $criteria->With, $qb);
        return $models;
    }
}

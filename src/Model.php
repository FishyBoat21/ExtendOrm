<?php
namespace FishyBoat21\ExtendOrm;

use FishyBoat21\ExtendOrm\Attribute\Column;
use FishyBoat21\ExtendOrm\Attribute\PrimaryKey;
use FishyBoat21\ExtendOrm\Attribute\Relation;
use FishyBoat21\ExtendOrm\Attribute\Relation\RelationType;
use FishyBoat21\ExtendOrm\Attribute\Table;
use FishyBoat21\ExtendOrm\QueryBuilder2\IQueryBuilder2;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilder2;
use FishyBoat21\ExtendOrm\QueryBuilder2\QueryBuilderOperator;

use PDO;
use ReflectionClass;
use ReflectionProperty;

abstract class Model {
    protected static $Table;
    protected static array $ModelMap = array();
    protected static bool $IsInitialize = false;
    protected IQueryBuilder2 $QueryBuilder;
    public function __construct(IQueryBuilder2 $queryBuilder) {
        if(!isset(static::$ModelMap[static::class])) {
            static::Initialize();
        }
        $this->QueryBuilder=$queryBuilder;
    }
    protected static function Initialize():void {
        static::$ModelMap[static::class] = new ModelMap();
        static::$Table = static::getTableName();
        $refClass = new ReflectionClass(static::class);
        $props = $refClass->getProperties();        
        foreach($props as $prop){
            $refProp = new ReflectionProperty(static::class,$prop->getName());
            $attributes = $refProp->getAttributes();
            foreach ($attributes as $attribute) {
                if($attribute->getName() == PrimaryKey::class && static::$ModelMap[static::class]->PrimaryKey == null){
                    static::$ModelMap[static::class]->PrimaryKey = $prop->getName();
                }
                if($attribute->getName() == Column::class){
                    $field = $attribute->getArguments()[0];
                    static::$ModelMap[static::class]->FieldPropMap[$field] = $prop->getName();
                }
                if($attribute->getName() == Relation::class){
                    $args = $attribute->getArguments();
                    // Expecting: type, target, foreignKey, localKey/ownerKey
                    $type = $args['type'] ?? null;
                    $target = $args['target'] ?? null;
                    $foreignKey = $args['foreignKey'] ?? null;
                    $localKey = $args['localKey'] ?? null;
                    $ownerKey = $args['ownerKey'] ?? null;
                    static::$ModelMap[static::class]->RelationMap[$prop->getName()] = [
                        "type" => $type,
                        "target" => $target,
                        "foreignKey" => $foreignKey,
                        "localKey" => $localKey,
                        "ownerKey" => $ownerKey
                    ];
                }
            }
        }

        if(static::$ModelMap[static::class]->PrimaryKey == null){
            throw new ExtendORMException("Primary key not set");
        }

        static::$IsInitialize = true;
    }
    
    public static function GetTableName(){
        $refClass = new ReflectionClass(static::class);
        $attributes = $refClass->getAttributes();
        foreach($attributes as $attribute){
            if($attribute->getName() == Table::class){
                return $attribute->getArguments()[0];
            }
        }
        throw new ExtendORMException("Table Not Defined");
    }
    protected function GetValues():array{
        $values = [];
        foreach(array_values(static::$ModelMap[static::class]->FieldPropMap) as $prop){
            $values[] = $this->$prop;
        }
        return $values;
    }
    
    protected static function GetPrimaryKey():string{
        $refClass = new ReflectionClass(static::class);
        $props = $refClass->getProperties();   
        $primaryKey = "";     
        foreach($props as $prop){
            $refProp = new ReflectionProperty(static::class,$prop->getName());
            $attributes = $refProp->getAttributes();
            foreach ($attributes as $attribute) {
                if($attribute->getName() == PrimaryKey::class){
                    $primaryKey = $prop->getName();
                }
            }
        }
        return $primaryKey;
    }

    public function Save() {
        if(!isset(static::$ModelMap[static::class])) {
            static::Initialize();
        }
        $values = $this->GetValues();
        $fields = array_keys(static::$ModelMap[static::class]->FieldPropMap);
        $primaryKey = static::$ModelMap[static::class]->PrimaryKey;
        $primaryKeyField = array_search($primaryKey,static::$ModelMap[static::class]->FieldPropMap);
        $data = array_combine($fields,$values);
        unset($data[$primaryKeyField]);
        if ($this->$primaryKey != null) {
            $this->QueryBuilder->update(static::GetTableName(),$data)
            ->where($primaryKeyField,QueryBuilderOperator::Equals,$this->$primaryKey)
            ->exec();
            return $this;
        } else {
            $this->$primaryKey = $this->QueryBuilder
            ->insert(static::GetTableName(),$data);
            return $this;
        }
    }

    public function Delete():void {
        $primaryKey = static::$ModelMap[static::class]->PrimaryKey;

        if ($primaryKey == null) {
            throw new ExtendORMException("Not a valid record");
        }

        // $result = QueryBuilder::delete(static::GetTableName())
        // ->where(array_search(static::$ModelMap[static::class]->PrimaryKey,static::$ModelMap[static::class]->FieldPropMap),QueryBuilderOperator::Equals,$this->$primaryKey)->query();
        $result = $this->QueryBuilder
        ->delete(static::GetTableName())
        ->where(
            array_search(static::$ModelMap[static::class]->PrimaryKey,static::$ModelMap[static::class]->FieldPropMap),
            QueryBuilderOperator::Equals,
            $this->$primaryKey)
        ->exec();

        if ($result == 1) {
            $this->$primaryKey = null;
        }else{
            throw new ExtendORMException();
        }
    }

    public function __get($name)
    {
        if(!isset(static::$ModelMap[static::class])) {
            static::Initialize();
        }
        if(isset(static::$ModelMap[static::class]->RelationMap[$name])){
            $relation = static::$ModelMap[static::class]->RelationMap[$name];
            $type = $relation["type"];
            $target = $relation["target"];
            $foreignKey = $relation["foreignKey"];
            $localKey = $relation["localKey"] ?? null;
            $ownerKey = $relation["ownerKey"] ?? null;

            if($type === RelationType::HasMany){
                $localValue = $this->$localKey;
                return $target::FindMany(new Criteria()->Add(new Criterion($foreignKey,QueryBuilderOperator::Equals,$localValue)), $this->QueryBuilder);
            }
            if($type ===  RelationType::BelongsTo){
                $foreignValue = $this->$foreignKey;
                return $target::FindOne(new Criteria()->Add(new Criterion($ownerKey,QueryBuilderOperator::Equals,$foreignValue)), $this->QueryBuilder);
            }
            if($type === RelationType::HasOne){
                $localValue = $this->$localKey;
                return $target::FindOne(new Criteria()->Add(new Criterion($foreignKey,QueryBuilderOperator::Equals,$localValue)), $this->QueryBuilder);
            }
        }
    }
    public static function FindMany(Criteria $criteria,IQueryBuilder2 $qb):array{
        $tableName = static::getTableName();
        static::Initialize();
        $query = $qb->select(implode(",",array_keys(static::$ModelMap[static::class]->FieldPropMap)))
        ->from($tableName);
        foreach($criteria->Criterion as $criterion){
            $fieldForSearch = array_search($criterion->Key,static::$ModelMap[static::class]->FieldPropMap);
            $query = $query->where($fieldForSearch,$criterion->Operator,$criterion->Value);
        }
        foreach($criteria->Sort as $sort){
            $fieldForSort = array_search($sort->Field,static::$ModelMap[static::class]->FieldPropMap);
            $query = $query->sort($fieldForSort,$sort->Direction);
        }        
        $results = $query->get();
        $resultObj = array();
        foreach($results as $result){
            $modelType = static::class;
            $model = new $modelType($qb);
            foreach($result as $key=>$value){
                $prop = static::$ModelMap[static::class]->FieldPropMap[$key];
                $model->$prop = $value;
            }
            $resultObj[] = $model;
        }
        return $resultObj;
    }
    public static function FindOne(Criteria $criteria,IQueryBuilder2 $qb):?static{
        $tableName = static::getTableName();
        static::Initialize();
        $query = $qb->select(implode(",",array_keys(static::$ModelMap[static::class]->FieldPropMap)))
        ->from($tableName);
        foreach($criteria->Criterion as $criterion){
            $fieldForSearch = array_search($criterion->Key,static::$ModelMap[static::class]->FieldPropMap);
            $query = $query->where($fieldForSearch,$criterion->Operator,$criterion->Value);
        }
        foreach($criteria->Sort as $sort){
            $fieldForSort = array_search($sort->Field,static::$ModelMap[static::class]->FieldPropMap);
            $query = $query->sort($fieldForSort,$sort->Direction);
        }
        if(count($result = $query->get()) == 0){
            return null;
        }
        $result = $result[0];
        $modelType = static::class;
        $model = new $modelType($qb);
        foreach($result as $key=>$value){
            $prop = static::$ModelMap[static::class]->FieldPropMap[$key];
            $model->$prop = $value;
        }
        return $model;
    }
    public static function Paging(int $limit,int $offset,Criteria $criteria,QueryBuilder2 $qb):array{
        $tableName = static::getTableName();
        static::Initialize();
        $query = $qb->select(implode(",",array_keys(static::$ModelMap[static::class]->FieldPropMap)))
        ->from($tableName);
        foreach($criteria->Criterion as $criterion){
            $fieldForSearch = array_search($criterion->Key,static::$ModelMap[static::class]->FieldPropMap);
            $query = $query->where($fieldForSearch,$criterion->Operator,$criterion->Value);
        }
        $results = $query->page($limit,$offset)->get();
        $resultObj = array();
        foreach($results as $result){
            $modelType = static::class;
            $model = new $modelType();
            foreach($result as $key=>$value){
                $prop = static::$ModelMap[static::class]->FieldPropMap[$key];
                $model->$prop = $value;
            }
            $resultObj[] = $model;
        }
        return $resultObj;
    }
}
?>
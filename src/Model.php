<?php
namespace FishyBoat21\ExtendOrm;

use FishyBoat21\ExtendOrm\Attribute\Column;
use FishyBoat21\ExtendOrm\Attribute\PrimaryKey;
use FishyBoat21\ExtendOrm\Attribute\Relation;
use FishyBoat21\ExtendOrm\Attribute\Table;
use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilder;
use FishyBoat21\ExtendOrm\QueryBuilder\QueryBuilderOperator;
use PDO;
use ReflectionClass;
use ReflectionProperty;

abstract class Model {
    protected static $Table;
    protected static array $ModelMap = array();
    protected static bool $IsInitialize = false;
    public function __construct() {
        if(!isset(static::$ModelMap[static::class])) {
            static::Initialize();
        }
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
        $values = $this->GetValues();
        $primaryKey = static::$ModelMap[static::class]->PrimaryKey;
        $primaryKeyField = array_search($primaryKey,static::$ModelMap[static::class]->FieldPropMap);
        if ($this->$primaryKey != null) {
            QueryBuilder::update(static::GetTableName())
            ->set(array_keys(static::$ModelMap[static::class]->FieldPropMap),$values)
            ->where($primaryKeyField,QueryBuilderOperator::Equals,$this->$primaryKey)
            ->query();
            return $this;
        } else {
            $field = array_keys(static::$ModelMap[static::class]->FieldPropMap);
            unset($field[static::GetPrimaryKey()]);
            $result = QueryBuilder::insert(static::GetTableName(),$field,$values)->query();
            if ($result) {
                $conn = Database::getInstance()->getConnection();
                $this->$primaryKey = $conn->lastInsertId();
            }
            return $this;
        }
    }

    public function Delete():void {
        $primaryKey = static::$ModelMap[static::class]->PrimaryKey;
        if ($primaryKey == null) {
            throw new ExtendORMException("Not a valid record");
        }
        $result = QueryBuilder::delete(static::GetTableName())
        ->where(array_search(static::$ModelMap[static::class]->PrimaryKey,static::$ModelMap[static::class]->FieldPropMap),QueryBuilderOperator::Equals,$this->$primaryKey)->query();
        if ($result) {
            $this->$primaryKey = null;
        }
    }

    public function __get($name)
    {
        if(isset(static::$ModelMap[static::class]->RelationMap[$name])){
            $relation = static::$ModelMap[static::class]->RelationMap[$name];
            $type = $relation["type"];
            $target = $relation["target"];
            $foreignKey = $relation["foreignKey"];
            $localKey = $relation["localKey"] ?? null;
            $ownerKey = $relation["ownerKey"] ?? null;

            if($type === Relation::$HasMany){
                // e.g. User hasMany Posts: User.id = Post.user_id
                $localValue = $this->$localKey;
                return $target::FindMany(QueryBuilderOperator::Equals, $localValue, $foreignKey);
            }
            if($type ===  Relation::$BelongsTo){
                // e.g. Post belongsTo User: Post.user_id = User.id
                $foreignValue = $this->$foreignKey;
                return $target::FindOne(QueryBuilderOperator::Equals, $foreignValue, $ownerKey);
            }
        }
    }
    public static function FindMany(QueryBuilderOperator $operator = QueryBuilderOperator::NotEqual,$value = 0,?string $propForSearch = null):array{
        if ($propForSearch == null){
            $propForSearch = static::GetPrimaryKey();
        }
        $tableName = static::getTableName();
        static::Initialize();
        $fieldForSearch = array_search($propForSearch,static::$ModelMap[static::class]->FieldPropMap);
        $results = QueryBuilder::select(array_keys(static::$ModelMap[static::class]->FieldPropMap),$tableName)->where($fieldForSearch,$operator,$value)->query()->fetchAll(\PDO::FETCH_ASSOC);
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
    public static function FindOne(QueryBuilderOperator $operator,$value,?string $propForSearch = null):?static{
        if ($propForSearch == null){
            $propForSearch = static::getPrimaryKey();
        }
        $tableName = static::getTableName();
        static::Initialize();
        $fieldForSearch = array_search($propForSearch,static::$ModelMap[static::class]->FieldPropMap);
        $result = QueryBuilder::select(array_keys(static::$ModelMap[static::class]->FieldPropMap),$tableName)->where($fieldForSearch,$operator,$value)->query()->fetch(\PDO::FETCH_ASSOC);
        if(!$result){
            return null;
        }
        $modelType = static::class;
        $model = new $modelType();
        foreach($result as $key=>$value){
            $prop = static::$ModelMap[static::class]->FieldPropMap[$key];
            $model->$prop = $value;
        }
        return $model;
    }
    public static function Paging(int $limit,int $offset,QueryBuilderOperator $operator = QueryBuilderOperator::NotEqual,$value = 0,?string $propForSearch = null):array{
        if ($propForSearch == null){
            $propForSearch = static::GetPrimaryKey();
        }
        $tableName = static::getTableName();
        static::Initialize();
        $fieldForSearch = array_search($propForSearch,static::$ModelMap[static::class]->FieldPropMap);
        $results = 
        QueryBuilder::select(array_keys(static::$ModelMap[static::class]->FieldPropMap),$tableName)
        ->where($fieldForSearch,$operator,$value)
        ->limit($limit,$offset)
        ->query()->fetchAll(\PDO::FETCH_ASSOC);
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
<?php
namespace Kevin1358\ExtendOrm;

use Kevin1358\ExtendOrm\Attribute\Column;
use Kevin1358\ExtendOrm\Attribute\PrimaryKey;
use Kevin1358\ExtendOrm\Attribute\Relation;
use Kevin1358\ExtendOrm\Attribute\Table;
use Kevin1358\ExtendOrm\QueryBuilder\QueryBuilder;
use Kevin1358\ExtendOrm\QueryBuilder\QueryBuilderOperator;
use PDO;
use ReflectionClass;
use ReflectionProperty;

abstract class Model {
    protected $table;
    protected $primaryKey;
    protected array $fieldPropMap;
    protected array $relationMap;
    public function __construct() {
        $this->table = static::getTableName();
        $refClass = new ReflectionClass($this);
        $props = $refClass->getProperties();        
        foreach($props as $prop){
            $refProp = new ReflectionProperty($this::class,$prop->getName());
            $attributes = $refProp->getAttributes();
            foreach ($attributes as $attribute) {
                if($attribute->getName() == PrimaryKey::class && $this->primaryKey == null){
                    $this->primaryKey = $prop->getName();
                }
                if($attribute->getName() == Column::class){
                    $field = $attribute->getArguments()[0];
                    $this->fieldPropMap[$field] = $prop->getName();
                }
                if($attribute->getName() == Relation::class){
                    $targetModel = $attribute->getArguments()[0];
                    $alias = $attribute->getArguments()[1];
                    $this->relationMap[$alias] = ["targetModel"=>$targetModel,"prop"=>$prop->getName()];
                }
            }
        }

        if($this->primaryKey == null){
            throw new ExtendORMException("Primary key not set");
        }
    }
    
    public static function getTableName(){
        $refClass = new ReflectionClass(static::class);
        $attributes = $refClass->getAttributes();
        foreach($attributes as $attribute){
            if($attribute->getName() == Table::class){
                return $attribute->getArguments()[0];
            }
        }
        throw new ExtendORMException("Table Not Defined");
    }
    protected function getValues():array{
        $values = [];
        foreach(array_values($this->fieldPropMap) as $prop){
            $values[] = $this->$prop;
        }
        return $values;
    }
    
    protected static function getPrimaryKey():string{
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

    public function save() {
        $conn = Database::getInstance()->getConnection();
        $values = $this->getValues();
        $primaryKey = $this->primaryKey;
        $primaryKeyField = array_search($primaryKey,$this->fieldPropMap);
        if ($this->$primaryKey != null) {
            QueryBuilder::update($this->getTableName())
            ->set(array_keys($this->fieldPropMap),$values)
            ->where($primaryKeyField,QueryBuilderOperator::Equals,$this->$primaryKey)
            ->query();
            return $this;
        } else {
            $field = array_keys($this->fieldPropMap);
            unset($field[$this->getPrimaryKey()]);
            $result = QueryBuilder::insert(static::getTableName(),$field,$values)->query();
            if ($result) {
                $this->$primaryKey = $conn->lastInsertId();
            }
            return $this;
        }
    }

    public function delete():void {
        $primaryKey = $this->primaryKey;
        if ($primaryKey == null) {
            throw new ExtendORMException("Not a valid record");
        }
        $result = QueryBuilder::delete(static::getTableName())
        ->where(array_search($this->primaryKey,$this->fieldPropMap),QueryBuilderOperator::Equals,$this->$primaryKey)->query();
        if ($result) {
            $this->$primaryKey = null;
        }
    }

    public function __get($name)
    {
        if(isset($this->relationMap[$name])){
            $relation = $this->relationMap[$name];
            $model = $relation["targetModel"];
            $prop = $relation["prop"];
            return $model::findOne(QueryBuilderOperator::Equals,$this->$prop);
        }
    }
    public static function findMany(QueryBuilderOperator $operator = QueryBuilderOperator::NotEqual,$value = 0,?string $propForSearch = null):array{
        if ($propForSearch == null){
            $propForSearch = static::getPrimaryKey();
        }
        $tableName = static::getTableName();
        $fieldPropMap = array();
        $refClass = new ReflectionClass(static::class);
        $props = $refClass->getProperties();        
        foreach($props as $prop){
            $refProp = new ReflectionProperty(static::class,$prop->getName());
            $attributes = $refProp->getAttributes();
            foreach ($attributes as $attribute) {
                if($attribute->getName() == Column::class){
                    $field = $attribute->getArguments()[0];
                    $fieldPropMap[$field] = $prop->getName();
                }
            }
        }
        $fieldForSearch = array_search($propForSearch,$fieldPropMap);
        $results = QueryBuilder::select(array_keys($fieldPropMap),$tableName)->where($fieldForSearch,$operator,$value)->query()->fetchAll(\PDO::FETCH_ASSOC);
        $resultObj = array();
        foreach($results as $result){
            $modelType = static::class;
            $model = new $modelType();
            foreach($result as $key=>$value){
                $prop = $fieldPropMap[$key];
                $model->$prop = $value;
            }
            $resultObj[] = $model;
        }
        return $resultObj;
    }
    public static function findOne(QueryBuilderOperator $operator,$value,?string $propForSearch = null):?static{
        if ($propForSearch == null){
            $propForSearch = static::getPrimaryKey();
        }
        $tableName = static::getTableName();
        $fieldPropMap = array();
        $refClass = new ReflectionClass(static::class);
        $props = $refClass->getProperties();        
        foreach($props as $prop){
            $refProp = new ReflectionProperty(static::class,$prop->getName());
            $attributes = $refProp->getAttributes();
            foreach ($attributes as $attribute) {
                if($attribute->getName() == Column::class){
                    $field = $attribute->getArguments()[0];
                    $fieldPropMap[$field] = $prop->getName();
                }
            }
        }
        $fieldForSearch = array_search($propForSearch,$fieldPropMap);
        $result = QueryBuilder::select(array_keys($fieldPropMap),$tableName)->where($fieldForSearch,$operator,$value)->query()->fetch(\PDO::FETCH_ASSOC);
        if(!$result){
            return null;
        }
        $modelType = static::class;
        $model = new $modelType();
        foreach($result as $key=>$value){
            $prop = $fieldPropMap[$key];
            $model->$prop = $value;
        }
        return $model;
    }
}
?>
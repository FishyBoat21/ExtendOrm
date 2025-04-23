<?php
namespace ExtendORM;

use ReflectionClass;
use ReflectionProperty;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $host = 'localhost';
        $dbname = 'extendorm';
        $username = 'root';
        $password = '';
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        try {
            $this->pdo = new \PDO($dsn, $username, $password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}

abstract class Model {
    protected $table;
    protected $primaryKey;
    protected array $fieldPropMap;
    protected array $relationMap;
    public function __construct($index = null) {
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
                    $this->relationMap[$prop->getName()] = $attribute->newInstance();
                }
            }
        }

        if($this->primaryKey == null){
            throw new ExtendORMException("Primary key not set");
        }

        if($index != null){
            $results = QueryBuilder::select(array_keys($this->fieldPropMap),$this->table)
            ->where(array_search($this->primaryKey,$this->fieldPropMap),QueryBuilderOperator::Equals,$index)
            ->query()->fetch(\PDO::FETCH_ASSOC);
            foreach($results as $key=>$value){
                $prop = $this->fieldPropMap[$key];
                $this->$prop = $value;
            }
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
    
    protected static function getPrimaryKey(){
        $refClass = new ReflectionClass(static::class);
        $props = $refClass->getProperties();        
        foreach($props as $prop){
            $refProp = new ReflectionProperty(static::class,$prop->getName());
            $attributes = $refProp->getAttributes();
            foreach ($attributes as $attribute) {
                if($attribute->getName() == PrimaryKey::class){
                    return $prop->getName();
                }
            }
        }
    }

    public function save() {
        $conn = Database::getInstance()->getConnection();
        $values = $this->getValues();
        $primaryKey = $this->primaryKey;
        if ($this->$primaryKey != null) {
            QueryBuilder::update($this->getTableName())
            ->set(array_keys($this->fieldPropMap),$values)
            ->where($this->primaryKey,QueryBuilderOperator::Equals,$this->$primaryKey)
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
        ->where($this->primaryKey,QueryBuilderOperator::Equals,$this->$primaryKey)->query();
        if ($result) {
            $this->$primaryKey = null;
        }
    }

    public function __get($name)
    {
        if(isset($this->relationMap[str_replace("Obj","Id",$name)])){
            $prop = str_replace("Obj","Id",$name);
            $relationObj = $this->relationMap[$prop];
            $model = $relationObj->targetModel;
            return new $model($this->$prop);
        }
    }
}
?>
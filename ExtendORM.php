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
    protected array $fields;
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
                    $this->fields[] = $field;
                }
            }
        }

        if($this->primaryKey == null){
            throw new ExtendORMException("Primary key not set");
        }

        if($index != null){
            $result = QueryBuilder::select($this->fields,$this->table)
            ->where($this->primaryKey,QueryBuilderOperator::Equals,$index)
            ->query()->fetch(\PDO::FETCH_NUM);
            
            for ($i=0; $i < count($this->fields); $i++) {
                $prop = $this->fields[$i];
                $this->$prop = $result[$i];
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
        foreach($this->fields as $field){
            $values[] = $this->$field;
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
            ->set($this->fields,$values)
            ->where($this->primaryKey,QueryBuilderOperator::Equals,$this->$primaryKey)
            ->query();
            return $this;
        } else {
            $result = QueryBuilder::insert(static::getTableName(),$this->fields,$values)->query();
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
}
?>
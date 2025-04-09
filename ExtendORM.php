<?php
namespace ExtendORM;
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
    protected $attributes = [];

    public function __construct($attributes = []) {
        $this->attributes = $attributes;
    }
    
    abstract public static function getTableName();
    
    abstract public static function getPrimaryKey();

    public function __get($name) {
        return $this->attributes[$name] ?? null;
    }

    public function __set($name, $value) {
        $this->attributes[$name] = $value;
    }

    public function save() {
        $conn = Database::getInstance()->getConnection();
        if (isset($this->attributes[static::getPrimaryKey()])) {
            $fields = [];
            $values = [];
            foreach ($this->attributes as $key => $value) {
                if ($key == static::getPrimaryKey()) continue;
                $fields[] = $key;
                $values[] = $value;
            }
            QueryBuilder::update($this->getTableName())
            ->set($fields,$values)
            ->where(static::getPrimaryKey(),QueryBuilderOperator::Equals,$this->attributes[static::getPrimaryKey()])
            ->query();
            return $this;
        } else {
            $fields = array_keys($this->attributes);
            $values = array_values($this->attributes);
            $result = QueryBuilder::insert(static::getTableName(),$fields,$values)->query();
            if ($result) {
                $this->attributes[static::getPrimaryKey()] = $conn->lastInsertId();
            }
            return $this;
        }
    }

    public static function find($id) {
        $result = QueryBuilder::select(["*"],static::getTableName())
        ->where(static::getPrimaryKey(),QueryBuilderOperator::Equals,$id)
        ->query()->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            return new static($result);
        }
        return null;
    }

    public function delete():void {
        if (!isset($this->attributes[static::getPrimaryKey()])) {
            throw new \Exception("Not a valid record");
        }
        $result = QueryBuilder::delete(static::getTableName())
        ->where(static::getPrimaryKey(),QueryBuilderOperator::Equals,$this->attributes[static::getPrimaryKey()])->query();
        if ($result) {
            unset($this->attributes[static::getPrimaryKey()]);
        }
    }
}
?>
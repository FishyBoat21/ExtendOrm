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
enum QueryBuilderOperator:string{
    case Equals = '=';
    case NotEqual = '!=';
    case LessThan = '<';
    case MoreThen = '>';
    case LessThenEquals = '<=';
    case MoreThenEquals = '>=';
}
interface IQueryBuilder{
    public static function insert(string $table, array $fields, array $values);
    public static function select(array $fields = ['*'], string $table):Queryable;
    public static function update(string $table):Set;
    public static function delete(string $table):Queryable;
}
class QueryBuilder implements IQueryBuilder {
    public Query $queryObj;

    public function __construct(Query $queryObj)
    {
        $this->queryObj = $queryObj;
    }
    public static function insert(string $table, array $fields, array $values)
    {
        $queryObj = new Query();
        $placeholders = array_fill(0, count($fields), '?');
        $queryObj->query = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $queryObj->values = $values;
        return new Queryable($queryObj);
    }
    public static function select(array $fields = ['*'], string $table):Queryable
    {
        $queryObj = new Query();
        $queryObj->query = "SELECT ".implode(', ', $fields)." FROM $table";
        return new Queryable($queryObj);
    }
    public static function update(string $table):Set
    {
        $queryObj = new Query();
        $queryObj->query = "UPDATE $table";
        return new Set($queryObj);
    }
    public static function delete(string $table):Queryable
    {
        $queryObj = new Query();
        $queryObj->query = "DELETE FROM $table";
        return new Queryable($queryObj);
    }
}
class Query{
    public string $query;
    public array $values = [];
}

class Set extends QueryBuilder{
    public function set(array $fields, array $values) {
        $this->queryObj->query .= " SET";
        $placeholders =[];
        foreach($fields as $field){
            $placeholders[] = " $field = ?";
        }
        $this->queryObj->query .= implode(",",$placeholders);
        $this->queryObj->values += $values;
        return new Queryable($this->queryObj);
    }
}
class Queryable extends QueryBuilder{
    
    public function where(string $field, QueryBuilderOperator $operator, $value) {
        $this->queryObj->query .= (strpos($this->queryObj->query, 'WHERE') === false) ? " WHERE $field $operator->value ?" : " AND $field $operator ?";
        $this->queryObj->values[] = $value;
        return $this;
    }

    public function orWhere(string $field, QueryBuilderOperator $operator, $value) {
        $this->queryObj->query .= " OR $field $operator ?";
        $this->queryObj->values[] = $value;
        return $this;
    }
    public function query(){
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare($this->queryObj->query);
        $stmt->execute($this->queryObj->values);
        return $stmt;
    }
}
?>
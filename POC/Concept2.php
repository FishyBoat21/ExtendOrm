<?php
namespace ORMPOC2;
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Configure your database settings here
        $host = 'localhost';
        $dbname = 'my_database';
        $username = 'root';
        $password = '';
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        try {
            $this->pdo = new \PDO($dsn, $username, $password);
            // Set error and exception handling
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    // Returns the singleton instance
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Returns the PDO connection
    public function getConnection() {
        return $this->pdo;
    }
}
abstract class Model {
    protected $primary_key;
    public $attributes = [];

    // Magic getter: allows property-like access
    public function __get($name) {
        return $this->attributes[$name] ?? null;
    }

    // Magic setter: allows property-like setting
    public function __set($name, $value) {
        $this->attributes[$name] = $value;
    }

    abstract public static function getTableName();
    
    abstract public static function getPrimaryKey();
}
class ORM{
    public static function save(Model $model) {
        $conn = Database::getInstance()->getConnection();
        if (isset($model->attributes[$model::getPrimaryKey()])) {
            // Update existing record
            $fields = [];
            $values = [];
            foreach ($model->attributes as $key => $value) {
                if ($key == $model::getPrimaryKey()) continue;
                $fields[] = "$key = ?";
                $values[] = $value;
            }
            // Add primary key value for the WHERE clause
            $values[] = $model->attributes[$model::getPrimaryKey()];
            $sql = "UPDATE " . $model::getTableName() . " SET " . implode(', ', $fields) . " WHERE " . $model::getPrimaryKey() . " = ?";
            $stmt = $conn->prepare($sql);
            return $stmt->execute($values);
        } else {
            // Insert new record
            $fields = array_keys($model->attributes);
            $placeholders = array_fill(0, count($fields), '?');
            $values = array_values($model->attributes);
            $sql = "INSERT INTO " . $model::getTableName() . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute($values);
            if ($result) {
                $model->attributes[$model::getPrimaryKey()] = $conn->lastInsertId();
            }
            return $result;
        }
    }

    // Find a record by its primary key
    public static function find(string $model, $id) {
        if(!class_exists($model)){
            throw new \Exception("Invalid model");
        }
        if(class_uses($model)[Model::class] === null){
            throw new \Exception("Invalid model");
        }
        $conn = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM " . $model::getTableName() . " WHERE " . $model::getPrimaryKey() . " = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            return new $model($result);
        }
        return null;
    }

    // Delete the current record by its primary key
    public static function delete(Model $model):void {
        if (!isset($model->attributes[$model::getPrimaryKey()])) {
            throw new \Exception("Not a valid record");
        }
        $conn = Database::getInstance()->getConnection();
        $sql = "DELETE FROM " . $model::getTableName() . " WHERE " . $model::getPrimaryKey() . " = ?";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$model->attributes[$model::getPrimaryKey()]]);
        if ($result) {
            unset($model);
        }
    }
}
?>
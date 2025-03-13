<?php
namespace ORMPOC1;

use Exception;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
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
                $fields[] = "$key = ?";
                $values[] = $value;
            }
            $values[] = $this->attributes[static::getPrimaryKey()];
            $sql = "UPDATE " . static::getTableName() . " SET " . implode(', ', $fields) . " WHERE " . static::getPrimaryKey() . " = ?";
            $stmt = $conn->prepare($sql);
            return $this;
        } else {
            $fields = array_keys($this->attributes);
            $placeholders = array_fill(0, count($fields), '?');
            $values = array_values($this->attributes);
            $sql = "INSERT INTO " . static::getTableName() . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute($values);
            if ($result) {
                $this->attributes[static::getPrimaryKey()] = $conn->lastInsertId();
            }
            return $this;
        }
    }

    public static function find($id) {
        $conn = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE " . static::getPrimaryKey() . " = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            return new static($result);
        }
        return null;
    }

    public function delete():void {
        if (!isset($this->attributes[static::getPrimaryKey()])) {
            throw new \Exception("Not a valid record");
        }
        $conn = Database::getInstance()->getConnection();
        $sql = "DELETE FROM " . static::getTableName() . " WHERE " . static::getPrimaryKey() . " = ?";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$this->attributes[static::getPrimaryKey()]]);
        if ($result) {
            unset($this->attributes[static::getPrimaryKey()]);
        }
    }
}


?>

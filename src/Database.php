<?php
namespace FishyBoat21\ExtendOrm;

use PDO;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct(PDO $connection) {
        $this->pdo = $connection;
    }

    public static function GetInstance():Database{
        return static::$instance;
    }
    public static function Boot(PDO $connection){
        static::$instance = new Database($connection);
    }
    public function GetConnection():PDO {
        return $this->pdo;
    }
}
?>
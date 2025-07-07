<?php
namespace Kevin1358\ExtendOrm;

use PDO;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct(PDO $connection) {
        $this->pdo = $connection;
    }

    public static function getInstance():Database{
        return self::$instance;
    }
    public static function boot(PDO $connection){
        self::$instance = new Database($connection);
    }
    public function getConnection():PDO {
        return $this->pdo;
    }
}
?>
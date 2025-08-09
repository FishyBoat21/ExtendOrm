<?php
namespace FishyBoat21\ExtendOrm;

use PDO;
use Throwable;

class Database {
    protected static $instance = null;
    protected PDO $pdo;

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
    public function Transaction($function){
        try {
            $this->pdo->beginTransaction();
            $function($this->pdo);
            $this->pdo->commit();
        } catch (Throwable $th) {
            $this->pdo->rollBack();
            throw $th;
        }
    }
}
?>
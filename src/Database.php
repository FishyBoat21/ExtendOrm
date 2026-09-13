<?php
namespace FishyBoat21\ExtendOrm;

use PDO;
use Throwable;

class Database {
    protected static $instance = null;
    protected PDO $pdo;
    protected int $transactionLevel = 0;

    private function __construct(PDO $connection) {
        $this->pdo = $connection;
    }

    public static function GetInstance(): Database {
        if (static::$instance === null) {
            throw new ExtendORMException("Database has not been booted. Call Database::Boot(PDO) first.");
        }
        return static::$instance;
    }
    public static function Boot(PDO $connection){
        static::$instance = new Database($connection);
    }
    public function GetConnection(): PDO {
        return $this->pdo;
    }
    public function Transaction(callable $function) {
        $isFirstTransaction = false;
        if ($this->transactionLevel === 0) {
            $this->pdo->beginTransaction();
            $isFirstTransaction = true;
        } else {
            $this->pdo->exec("SAVEPOINT trans_{$this->transactionLevel}");
        }
        $this->transactionLevel++;

        try {
            $result = $function($this->pdo);
            $this->transactionLevel--;
            if ($isFirstTransaction) {
                $this->pdo->commit();
            } else {
                $this->pdo->exec("RELEASE SAVEPOINT trans_{$this->transactionLevel}");
            }
            return $result;
        } catch (Throwable $th) {
            $this->transactionLevel--;
            if ($isFirstTransaction) {
                $this->pdo->rollBack();
            } else {
                $this->pdo->exec("ROLLBACK TO SAVEPOINT trans_{$this->transactionLevel}");
            }
            throw $th;
        }
    }
}
?>
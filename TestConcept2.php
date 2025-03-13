<?php
require_once "./POC/Concept2.php";
use ORMPOC2\Model;
use ORMPOC2\ORM;
class User extends Model {
    // Returns the database table name for the User model
    public static function getTableName() {
        return 'user';
    }
    public static function getPrimaryKey()
    {
        return "id";
    }
}
$time_start = microtime(true); 
$users = [];
for ($i=0; $i < 100; $i++) { 
    $user = new User(["name"=>"Joko"]);
    ORM::save($user);
    $users[] = $user;
}
foreach($users as $user){
    $userFound = ORM::find(User::class,$user->id);
    $userFound->name = "Aldi";
    ORM::save($userFound);
}
foreach($users as $user){
    ORM::delete($user);
}
$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
echo '<b>Total Execution Time:</b> '.$execution_time.' sec';
?>
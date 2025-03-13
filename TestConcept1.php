<?php
include_once "./POC/Concept1.php";
use ORMPOC1\Model;
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
    $user->save();
    $users[] = $user;
}
foreach($users as $user){
    $userFound = User::find($user->id);
    $userFound->name = "Aldi";
    $userFound->save();
}
foreach($users as $user){
    $user->delete();
}
$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
echo '<b>Total Execution Time:</b> '.$execution_time.' sec';
?>

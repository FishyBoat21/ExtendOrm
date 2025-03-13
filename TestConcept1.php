<?php
use ORMPOC1\Model;
class User extends Model {
    // Returns the database table name for the User model
    public static function getTableName() {
        return 'users';
    }
    public static function getPrimaryKey()
    {
        return "id";
    }
}
$user = new User(["name"=>"Joko"]);
$userFound = User::find(1);
$userFound->name = "Aldi";
$userFound->save()->delete();
?>

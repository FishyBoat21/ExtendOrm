<?php
use ORMPOC2\Model;
use ORMPOC2\ORM;
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
ORM::save($user);
$userFound = ORM::find(User::class,"1");
$userFound->name = "Aldi";
ORM::delete($userFound);

?>
<?php
namespace ExtendORM;
enum QueryBuilderOperator:string{
    case Equals = '=';
    case NotEqual = '!=';
    case LessThan = '<';
    case MoreThen = '>';
    case LessThenEquals = '<=';
    case MoreThenEquals = '>=';
}
interface IQueryBuilder{
    public static function insert(string $table, array $fields, array $values);
    public static function select(array $fields = ['*'], string $table):Queryable;
    public static function update(string $table):Set;
    public static function delete(string $table):Queryable;
}
class QueryBuilder implements IQueryBuilder {
    public Query $queryObj;

    public function __construct(Query $queryObj)
    {
        $this->queryObj = $queryObj;
    }
    public static function insert(string $table, array $fields, array $values)
    {
        $queryObj = new Query();
        $placeholders = array_fill(0, count($fields), '?');
        $queryObj->query = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $queryObj->values = $values;
        return new Queryable($queryObj);
    }
    public static function select(array $fields = ['*'], string $table):Queryable
    {
        $queryObj = new Query();
        $queryObj->query = "SELECT ".implode(', ', $fields)." FROM $table";
        return new Queryable($queryObj);
    }
    public static function update(string $table):Set
    {
        $queryObj = new Query();
        $queryObj->query = "UPDATE $table";
        return new Set($queryObj);
    }
    public static function delete(string $table):Queryable
    {
        $queryObj = new Query();
        $queryObj->query = "DELETE FROM $table";
        return new Queryable($queryObj);
    }
}
class Query{
    public string $query;
    public array $values = [];
}

class Set extends QueryBuilder{
    public function set(array $fields, array $values) {
        $this->queryObj->query .= " SET";
        $placeholders =[];
        foreach($fields as $field){
            $placeholders[] = " $field = ?";
        }
        $this->queryObj->query .= implode(",",$placeholders);
        $this->queryObj->values += $values;
        return new Queryable($this->queryObj);
    }
}
class Queryable extends QueryBuilder{
    
    public function where(string $field, QueryBuilderOperator $operator, $value) {
        $this->queryObj->query .= (strpos($this->queryObj->query, 'WHERE') === false) ? " WHERE $field $operator->value ?" : " AND $field $operator ?";
        $this->queryObj->values[] = $value;
        return $this;
    }

    public function orWhere(string $field, QueryBuilderOperator $operator, $value) {
        $this->queryObj->query .= " OR $field $operator ?";
        $this->queryObj->values[] = $value;
        return $this;
    }
    public function query(){
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare($this->queryObj->query);
        $stmt->execute($this->queryObj->values);
        return $stmt;
    }
}
?>
<?php
namespace Kevin1358\ExtendOrm\QueryBuilder;

enum QueryBuilderOperator:string{
    case Equals = '=';
    case NotEqual = '!=';
    case LessThan = '<';
    case MoreThan = '>';
    case LessThanEquals = '<=';
    case MoreThanEquals = '>=';
    case Like = 'LIKE';
    case NotLike = 'NOT LIKE';
}
?>
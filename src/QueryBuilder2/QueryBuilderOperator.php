<?php
namespace FishyBoat21\ExtendOrm\QueryBuilder2;

enum QueryBuilderOperator:string{
    case Equals = '=';
    case NotEqual = '!=';
    case LessThan = '<';
    case MoreThan = '>';
    case LessThanEquals = '<=';
    case MoreThanEquals = '>=';
    case Like = 'LIKE';
    case NotLike = 'NOT LIKE';
    case Is = 'IS';
}
?>
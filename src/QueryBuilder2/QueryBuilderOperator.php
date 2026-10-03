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
    /** Null-safe comparison. Only accepts a null value; see IsNull / IsNotNull. */
    case Is = 'IS';
    case IsNull = 'IS NULL';
    case IsNotNull = 'IS NOT NULL';
    /** Expects a non-empty array of values. */
    case In = 'IN';
    case NotIn = 'NOT IN';
    /** Expects a two-element array: [minimum, maximum]. */
    case Between = 'BETWEEN';
    case NotBetween = 'NOT BETWEEN';
}

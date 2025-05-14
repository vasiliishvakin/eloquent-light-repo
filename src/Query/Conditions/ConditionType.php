<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Query\Conditions;

enum ConditionType: string
{
    case In = 'in';
    case NotIn = 'not_in';
    case Between = 'between';
    case NotBetween = 'not_between';
    case Null = 'null';
    case NotNull = 'not_null';
    case Like = 'like';
    case NotLike = 'not_like';
}

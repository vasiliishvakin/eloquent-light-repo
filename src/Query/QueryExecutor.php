<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Query;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Vaskiq\EloquentLightRepo\Contracts\QueryExecutorInterface;

enum QueryExecutor implements QueryExecutorInterface
{
    public const PARAM_COLUMNS = 'columns';

    public const PARAM_COLUMNS_DEFAULT = ['*'];

    public function apply(Builder $query, array $params = []): mixed
    {
        return match ($this) {
            self::Get => $query->get($params[self::PARAM_COLUMNS] ?? $params[0] ?? self::PARAM_COLUMNS_DEFAULT),
            self::First => $query->first($params[self::PARAM_COLUMNS] ?? $params[0] ?? self::PARAM_COLUMNS_DEFAULT),
            self::FirstOrFail => $query->firstOrFail($params[self::PARAM_COLUMNS] ?? $params[0] ?? self::PARAM_COLUMNS_DEFAULT),
            self::Paginate => $query->paginate(...$params),
            self::SimplePaginate => $query->simplePaginate(...$params),
            self::CursorPaginate => $query->cursorPaginate(...$params),
        };
    }

    public static function Custom(Closure $closure): QueryExecutorInterface
    {
        return ClosureQueryExecutor::from($closure);
    }

    case Get;
    case First;
    case FirstOrFail;
    case Paginate;
    case SimplePaginate;
    case CursorPaginate;
}

<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Query;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Vaskiq\EloquentLightRepo\Contracts\QueryExecutorInterface;

final class ClosureQueryExecutor implements QueryExecutorInterface
{
    /**
     * @param  Closure(Builder, mixed ...$params): mixed  $closure
     */
    public function __construct(
        private readonly Closure $closure
    ) {}

    /**
     * @param  Closure(Builder, mixed ...$params): mixed  $closure
     */
    public static function from(Closure $closure): self
    {
        return new self($closure);
    }

    public function apply(Builder $query, array $params = []): mixed
    {
        return ($this->closure)($query, ...$params);
    }
}

<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface QueryExecutorInterface
{
    public function apply(Builder $query, array $params = []): mixed;
}

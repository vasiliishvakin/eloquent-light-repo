<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Vaskiq\EloquentLightRepo\Query\ClosureQueryExecutor;

it('executes a closure and returns the result', function () {
    $closure = fn (Builder $query) => 'test result';
    $executor = ClosureQueryExecutor::from($closure);

    $mockQuery = $this->createMock(Builder::class);
    expect($executor->apply($mockQuery))->toBe('test result');
});

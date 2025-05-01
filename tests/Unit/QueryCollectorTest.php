<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Vaskiq\EloquentLightRepo\Support\QueryCollector;

it('logs queries executed within a callback', function () {
    $collected = [];

    QueryCollector::collect(function () {
        DB::statement('SELECT 1');
    }, $collected);

    expect($collected)->toHaveCount(1)
        ->and($collected[0]['sql'])->toBe('SELECT 1');
});

it('returns an empty array when no queries are logged', function () {
    QueryCollector::stopQueryLog();

    expect(QueryCollector::collected())->toBe([]);
});

test('QueryCollector logging toggles query state flag', function () {
    $class = new class
    {
        use \Vaskiq\EloquentLightRepo\Support\QueryCollector;

        public function isLoggingEnabled(): bool
        {
            return self::logging();
        }
    };

    expect($class->isLoggingEnabled())->toBeFalse();

    $class::startQueryLog();
    expect($class->isLoggingEnabled())->toBeTrue();

    $class::stopQueryLog();
    expect($class->isLoggingEnabled())->toBeFalse();
});

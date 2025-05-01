<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Support;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

trait QueryCollector
{
    protected static ?array $queries = null;

    protected static bool $collectorActive = false;

    public static function startQueryLog(): void
    {
        self::$queries = [];

        if (! self::$collectorActive) {
            self::$collectorActive = true;

            DB::listen(function (QueryExecuted $query) {
                if (self::$queries !== null) {
                    self::$queries[] = [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => (string) $query->time.' ms',
                        'memory' => memory_get_usage().' bytes',
                    ];
                }
            });
        }
    }

    public static function stopQueryLog(): void
    {
        self::$queries = null;
    }

    public static function collected(): array
    {
        return self::$queries ?? [];
    }

    public static function logging(): bool
    {
        return self::$queries !== null;
    }

    public static function collect(callable $callback, array &$collected): mixed
    {
        self::startQueryLog();
        try {
            return tap($callback(), function () use (&$collected) {
                $collected = self::collected();
            });
        } finally {
            self::stopQueryLog();
        }
    }
}

<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Contracts;

interface QueryCollectorInterface
{
    public static function startQueryLog(): void;

    public static function stopQueryLog(): void;

    public static function collected(): array;

    public static function logging(): bool;

    public static function collect(callable $callback, array &$collected): mixed;
}

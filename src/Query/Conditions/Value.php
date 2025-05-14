<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Query\Conditions;

use Illuminate\Support\Str;

/**
 * @method static self in(array $values)
 * @method static self notIn(array $values)
 * @method static self between(array $range)
 * @method static self notBetween(array $range)
 * @method static self null()
 * @method static self notNull()
 * @method static self like(string $pattern)
 * @method static self notLike(string $pattern)
 */
class Value
{
    public function __construct(
        public readonly ConditionType $type,
        public readonly mixed $value,
    ) {}

    public static function from(ConditionType $type, mixed $value = null, ...$args): static
    {
        return new static($type, $value);
    }

    public static function __callStatic(string $method, array $args): static
    {
        $enumBack = Str::snake($method);

        $conditionType = ConditionType::tryFrom($enumBack);

        if ($conditionType === null) {
            throw new \BadMethodCallException("Method {$method} does not exist.");
        }

        return self::from($conditionType, ...$args);
    }
}

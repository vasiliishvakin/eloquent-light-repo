<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Query;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as RawBuilder;
use Stringable;
use Vaskiq\EloquentLightRepo\Query\Conditions\ConditionType;
use Vaskiq\EloquentLightRepo\Query\Conditions\Value;

class QueryCriteria
{
    private bool $processed = false;

    public function __construct(
        public readonly int|string|array|Value|Stringable|null $conditions = null,
        public readonly ?Closure $queryModifier = null,
    ) {}

    public function __invoke(EloquentBuilder|RawBuilder $query): mixed
    {
        if ($this->processed) {
            return $query;
        }
        $this->processed = true;

        if ($this->isEmptyCondition()) {
            return $query;
        }

        if ($this->isPrimaryKeyCondition()) {
            return $this->applyPrimaryKeyCondition($query);
        }

        if (is_array($this->conditions)) {
            if ($this->isListOfSingleFieldConditions($this->conditions)) {
                return $this->applyListOfSingleFieldConditions($query);
            }

            return $this->applyArrayConditions($query);
        }

        if ($this->queryModifier) {
            $query = ($this->queryModifier)($query);
        }

        return $query;
    }

    public function applyConditionValue(EloquentBuilder|RawBuilder $query, string $field, Value $value): void
    {
        match ($value->type) {
            ConditionType::In => $query->whereIn($field, $value->value),
            ConditionType::NotIn => $query->whereNotIn($field, $value->value),
            ConditionType::Null => $query->whereNull($field),
            ConditionType::NotNull => $query->whereNotNull($field),
            ConditionType::Between => $query->whereBetween($field, $value->value),
            ConditionType::NotBetween => $query->whereNotBetween($field, $value->value),
            ConditionType::Like => $query->whereLike($field, $value->value),
            ConditionType::NotLike => $query->whereNotLike($field, $value->value),
        };
    }

    public function getQualifiedKeyName(EloquentBuilder|RawBuilder $query): string
    {
        if (! $query instanceof EloquentBuilder) {
            throw new \RuntimeException('Primary key condition is only supported on EloquentBuilder');
        }

        return $query->getModel()->getQualifiedKeyName();
    }

    protected function isEmptyCondition(): bool
    {
        return $this->conditions === null
            || (is_array($this->conditions) && $this->conditions === []);
    }

    protected function isPrimaryKeyCondition(): bool
    {
        return is_int($this->conditions)
            || is_string($this->conditions)
            || $this->conditions instanceof Stringable
            || $this->conditions instanceof Value;
    }

    protected function applyPrimaryKeyCondition(EloquentBuilder|RawBuilder $query): mixed
    {
        if (! $query instanceof EloquentBuilder) {
            throw new \RuntimeException('Primary key condition is only supported on EloquentBuilder');
        }

        if ($this->conditions instanceof Value) {
            if ($this->conditions->type !== ConditionType::In) {
                $this->applyConditionValue($query, $this->getQualifiedKeyName($query), $this->conditions);

                return $query;
            }

            $value = $this->conditions->value;
        } else {
            $value = $this->conditions;
        }

        $key = $this->getQualifiedKeyName($query);

        if (is_array($value)) {
            return $query->whereIn($key, $value);
        }

        return $query->whereKey((string) $value);
    }

    protected function isListOfSingleFieldConditions(array $conditions): bool
    {
        return array_is_list($conditions)
            && count($conditions) > 0
            && is_array($conditions[0])
            && ! array_is_list($conditions[0]);
    }

    protected function applyListOfSingleFieldConditions(EloquentBuilder|RawBuilder $query): mixed
    {
        foreach ($this->conditions as $sub) {
            (new self($sub))($query);
        }

        return $query;
    }

    protected function applyArrayConditions(EloquentBuilder|RawBuilder $query): mixed
    {
        foreach ($this->conditions as $key => $value) {
            if ($value instanceof Value) {
                $this->applyConditionValue($query, $key, $value);

            } elseif ($value instanceof Stringable) {
                $query->where($key, (string) $value);

            } elseif (is_array($value)) {
                if (array_is_list($value)) {
                    $query->whereIn($key, $value);
                } else {
                    throw new \InvalidArgumentException("Array value for [$key] must be a list or a Value instance.");
                }

            } elseif ($value === null) {
                $query->whereNull($key);

            } else {
                $query->where($key, $value);
            }
        }

        return $query;
    }
}

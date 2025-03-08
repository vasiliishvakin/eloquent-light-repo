<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Vaskiq\EloquentLightRepo\Contracts\EloquentRepositoryInterface;
use Vaskiq\EloquentLightRepo\Exceptions\CreateException;
use Vaskiq\EloquentLightRepo\Exceptions\UpdateException;

abstract class EloquentRepository implements EloquentRepositoryInterface
{
    protected ?bool $useTransaction = true;

    protected array $defaultConditions = [];

    protected static bool $isQueryListenerRegistered = false;

    public function __construct(protected readonly Model $model) {}

    public function withQueryCollection(?array &$collected = null): self
    {
        $collected = [];

        if (! self::$isQueryListenerRegistered) {
            self::$isQueryListenerRegistered = true;

            DB::listen(function ($query) use (&$collected) {
                $collected[] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time.' ms',
                ];
            });
        }

        return $this;
    }

    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->query()->find($id, $columns);
    }

    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->query()->findOrFail($id, $columns);
    }

    public function findBy(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): Collection
    {
        return $this->buildQuery($conditions, $queryModifier)->get($columns);
    }

    public function findFirst(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): ?Model
    {
        return $this->buildQuery($conditions, $queryModifier)->first($columns);
    }

    public function findFirstOrFail(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): Model
    {
        return $this->buildQuery($conditions, $queryModifier)->firstOrFail($columns);
    }

    public function create(array $data): Model
    {
        try {
            return $this->model->create($data);
        } catch (\Exception $e) {
            throw CreateException::failed($this->modelClass(), $e);
        }
    }

    public function update(int|string $id, array $data, array $options = []): ?Model
    {
        $useTransaction = config('repository.use_transactions', $this->useTransaction);

        $callback = function () use ($id, $data, $options) {
            $model = $this->find($id);
            if ($model) {
                try {
                    $model->update($data, $options);
                } catch (\Exception $e) {
                    throw UpdateException::failed($this->modelClass(), $e);
                }
            }

            return $model;
        };

        return $useTransaction && method_exists($this->model->getConnection(), 'transaction')
            ? $this->model->getConnection()->transaction($callback)
            : $callback();
    }

    public function delete(int|string $id): bool
    {
        $model = $this->find($id);

        return $model ? $this->deleteModel($model) : false;
    }

    public function deleteModel(Model $model): bool
    {
        return $model->delete();
    }

    public function deleteBy(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null): int
    {
        return $this->buildQuery($conditions, $queryModifier)->delete();
    }

    public function chunk(int $count, Closure $callback, ?Closure $queryModifier = null): void
    {
        $query = $this->query();

        if ($queryModifier) {
            $queryModifier($query);
        }

        $query->chunk($count, $callback);
    }

    public function exists(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, bool $forceRaw = false): bool
    {
        $query = $forceRaw
            ? tap($this->raw(), fn ($q) => $conditions ? $q->where($conditions) : null)
            : $this->buildQuery($conditions, $queryModifier);

        return $query->exists();
    }

    public function count(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, bool $forceRaw = false): int
    {
        $query = $forceRaw
            ? tap($this->raw(), fn ($q) => $conditions ? $q->where($conditions) : null)
            : $this->buildQuery($conditions, $queryModifier);

        return $query->count();
    }

    public function pluck(string $column, ?string $key = null, ?Closure $queryModifier = null, bool $forceRaw = false): Collection
    {
        $query = $forceRaw
            ? tap($this->raw(), fn ($q) => $queryModifier ? $queryModifier($q) : null)
            : $this->buildQuery(null, $queryModifier);

        return $query->pluck($column, $key);
    }

    public function query(): EloquentBuilder
    {
        $query = $this->model->newModelQuery();

        if (! empty($this->defaultConditions)) {
            $query->where($this->defaultConditions);
        }

        return $query;
    }

    public function raw(): QueryBuilder
    {
        return $this->model->getConnection()->table($this->model->getTable());
    }

    public function modelClass(): string
    {
        return $this->model::class;
    }

    public function new(): Model
    {
        return app($this->model::class);
    }

    public function updateOrCreate(array $attributes, array $values): Model
    {
        return $this->query()->updateOrCreate($attributes, $values);
    }

    protected function buildQuery(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null): EloquentBuilder
    {
        $query = $this->query();
        if ($conditions) {
            $query->where($conditions);
        }
        if ($queryModifier) {
            $queryModifier($query);
        }

        return $query;
    }
}

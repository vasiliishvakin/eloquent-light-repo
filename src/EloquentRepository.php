<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Vaskiq\EloquentLightRepo\Contracts\EloquentRepositoryInterface;
use Vaskiq\EloquentLightRepo\Contracts\QueryCollectorInterface;
use Vaskiq\EloquentLightRepo\Contracts\QueryExecutorInterface;
use Vaskiq\EloquentLightRepo\Query\QueryExecutor;
use Vaskiq\EloquentLightRepo\Support\QueryCollector;

/**
 * Base Eloquent repository implementation.
 *
 * @template TModel of Model
 */
abstract class EloquentRepository implements EloquentRepositoryInterface, QueryCollectorInterface
{
    use QueryCollector;

    protected ?bool $useTransaction = true;

    protected ?Closure $defaultQueryClosure = null;

    /**
     * Constructor.
     *
     * @param  TModel  $model  The Eloquent model instance
     */
    public function __construct(protected readonly Model $model, ?Closure $defaultQueryClosure = null)
    {
        if ($defaultQueryClosure) {
            $this->defaultQueryClosure = $defaultQueryClosure;
        }
    }

    /**
     * Get the model class name.
     *
     * @return class-string<TModel>
     */
    public function modelClass(): string
    {
        return $this->model::class;
    }

    public function getTable(): string
    {
        return $this->model->getTable();
    }

    public function transaction(Closure $callback): mixed
    {
        return $this->model->getConnection()->transaction($callback);
    }

    /**
     * Find a model by its primary key.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     * @return TModel|null
     */
    public function find(int|string $id, ?Closure $queryModifier = null, array $columns = ['*']): ?Model
    {
        return $this->runQuery(
            static fn ($q) => $q->whereKey($id),
            $queryModifier,
            QueryExecutor::First,
            ['columns' => $columns]
        );
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     * @return TModel
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int|string $id, ?Closure $queryModifier = null, array $columns = ['*']): Model
    {
        return $this->runQuery(
            static fn ($q) => $q->whereKey($id),
            $queryModifier,
            QueryExecutor::FirstOrFail,
            ['columns' => $columns]
        );
    }

    /**
     * Find models by conditions.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     * @return Collection<int, TModel>
     */
    public function findBy(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): Collection
    {
        return $this->runQuery(
            $conditions,
            $queryModifier,
            QueryExecutor::Get,
            ['columns' => $columns]
        );
    }

    /**
     * Find the first model matching conditions.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     * @return TModel|null
     */
    public function findFirst(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): ?Model
    {
        return $this->runQuery(
            $conditions,
            $queryModifier,
            QueryExecutor::First,
            ['columns' => $columns]
        );
    }

    /**
     * Find the first model matching conditions or throw an exception.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     * @return TModel
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findFirstOrFail(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): Model
    {
        return $this->runQuery(
            $conditions,
            $queryModifier,
            QueryExecutor::FirstOrFail,
            ['columns' => $columns]
        );
    }

    /**
     * Paginate the results using LengthAwarePaginator.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     * @param  array  $params  Additional parameters passed to paginate (e.g., ['perPage' => 15, 'page' => 1])
     */
    public function paginate(
        array|Closure|Expression|null $conditions = null,
        ?Closure $queryModifier = null,
        array $params = []
    ): LengthAwarePaginator {
        return $this->runQuery($conditions, $queryModifier, QueryExecutor::Paginate, $params);
    }

    /**
     * Paginate the results using SimplePaginator.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     * @param  array  $params  Additional parameters passed to simplePaginate
     */
    public function simplePaginate(
        array|Closure|Expression|null $conditions = null,
        ?Closure $queryModifier = null,
        array $params = []
    ): Paginator {
        return $this->runQuery($conditions, $queryModifier, QueryExecutor::SimplePaginate, $params);
    }

    /**
     * Paginate the results using CursorPaginator.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     * @param  array  $params  Additional parameters passed to cursorPaginate (e.g., ['perPage' => 10])
     */
    public function cursorPaginate(
        array|Closure|Expression|null $conditions = null,
        ?Closure $queryModifier = null,
        array $params = []
    ): CursorPaginator {
        return $this->runQuery($conditions, $queryModifier, QueryExecutor::CursorPaginate, $params);
    }

    /**
     * Create a new model instance.
     *
     * @return TModel
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a model by its primary key.
     *
     * @return TModel|null
     */
    public function update(int|string $id, array $data, array $options = []): ?Model
    {
        $useTransaction = config('repository.use_transactions', $this->useTransaction);

        $callback = function () use ($id, $data, $options) {
            $model = $this->find($id);
            if ($model) {
                $model->update($data, $options);
            }

            return $model;
        };

        return $useTransaction && method_exists($this->model->getConnection(), 'transaction')
            ? $this->transaction($callback)
            : $callback();
    }

    /**
     * Delete a model by its primary key.
     */
    public function delete(int|string $id): bool
    {
        $model = $this->find($id);

        return $model ? $this->deleteModel($model) : false;
    }

    /**
     * Delete a model instance.
     */
    public function deleteModel(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Delete models by conditions.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     * @return int Number of records deleted
     */
    public function deleteBy(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null): int
    {
        return $this->buildQuery($conditions, $queryModifier)->delete();
    }

    /**
     * Chunk through models.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     */
    public function chunk(int $count, Closure $callback, ?Closure $queryModifier = null): void
    {
        $query = $this->query();

        if ($queryModifier) {
            $queryModifier($query);
        }

        $query->chunk($count, $callback);
    }

    /**
     * Determine if any records exist for the given conditions.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     */
    public function exists(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, bool $forceRaw = false): bool
    {
        $query = $forceRaw
            ? tap($this->raw(), fn ($q) => $conditions ? $q->where($conditions) : null)
            : $this->buildQuery($conditions, $queryModifier);

        return $query->exists();
    }

    /**
     * Count the number of records for the given conditions.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     */
    public function count(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, bool $forceRaw = false): int
    {
        $query = $forceRaw
            ? tap($this->raw(), fn ($q) => $conditions ? $q->where($conditions) : null)
            : $this->buildQuery($conditions, $queryModifier);

        return $query->count();
    }

    /**
     * Pluck a single column's values from the database.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     */
    public function pluck(string $column, ?string $key = null, ?Closure $queryModifier = null, bool $forceRaw = false): Collection
    {
        $query = $forceRaw
            ? tap($this->raw(), fn ($q) => $queryModifier ? $queryModifier($q) : null)
            : $this->buildQuery(null, $queryModifier);

        return $query->pluck($column, $key);
    }

    /**
     * Get a new Eloquent query builder for the model.
     */
    public function query(): EloquentBuilder
    {
        $query = $this->model->newModelQuery();

        if ($this->defaultQueryClosure instanceof Closure) {
            ($this->defaultQueryClosure)($query);
        }

        return $query;
    }

    /**
     * Get a new raw query builder for the model's table.
     */
    public function raw(): QueryBuilder
    {
        return $this->model->getConnection()->table($this->model->getTable());
    }

    /**
     * Get a new instance of the model.
     *
     * @return TModel
     */
    public function new(): Model
    {
        return app($this->model::class);
    }

    /**
     * Update or create a model.
     *
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values): Model
    {
        return $this->query()->updateOrCreate($attributes, $values);
    }

    /**
     * Build an Eloquent query with optional conditions and modifier.
     *
     * @param  (Closure(EloquentBuilder):void)|null  $queryModifier
     */
    public function buildQuery(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null): EloquentBuilder
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

    public function runQuery(
        array|Closure|Expression|null $conditions = null,
        ?Closure $queryModifier = null,
        QueryExecutorInterface|Closure $executor = QueryExecutor::Get,
        array $params = []
    ): mixed {
        $query = $this->buildQuery($conditions, $queryModifier);
        if ($executor instanceof Closure) {
            $executor = QueryExecutor::Custom($executor);
        }

        return $executor->apply($query, $params);
    }

    public function runCustomQuery(Closure $closure): mixed
    {
        return QueryExecutor::Custom($closure)->apply($this->query());
    }

    /**
     * Refresh only the given or loaded relations of the model.
     *
     * @param  TModel  $model
     * @param  array<string>|null  $relations
     * @return TModel
     */
    public function refreshRelations(Model $model, ?array $relations = null): Model
    {
        if ($relations === null) {
            $relations = array_keys($model->getRelations());
        }

        foreach ($relations as $relation) {
            $model->unsetRelation(Str::before($relation, '.'));
        }

        return $model->load($relations);
    }

    /**
     * Refresh the model instance and optionally its relations.
     *
     * @param  TModel  $model
     * @param  array<string>|false|null  $relations
     * @return TModel
     */
    public function refresh(Model $model, array|false|null $relations = null): Model
    {
        $model->refresh();

        if ($relations === false) {
            return $model;
        }

        return $this->refreshRelations($model, $relations);
    }
}

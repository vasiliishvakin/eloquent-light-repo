<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Contracts;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Stringable;
use Vaskiq\EloquentLightRepo\Query\QueryCriteria;

/**
 * @template TModel of Model
 */
interface EloquentRepositoryInterface
{
    /**
     * Finds an Eloquent model by its ID.
     *
     * @param  int|string  $id  The ID of the model to find
     * @param  array  $columns  The columns to select
     * @param  Closure|null  $queryCallback  Optional callback to modify the query
     * @return TModel|null
     */
    public function find(int|string $id, ?Closure $queryModifier = null, array $columns = ['*']): ?Model;

    /**
     * Finds an Eloquent model by its ID or throws an exception.
     *
     * @param  int|string  $id  The ID of the model to find
     * @param  array  $columns  The columns to select
     * @param  Closure|null  $queryCallback  Optional callback to modify the query
     * @return TModel
     */
    public function findOrFail(int|string $id, ?Closure $queryModifier = null, array $columns = ['*']): Model;

    /**
     * Finds Eloquent models based on a set of conditions.
     *
     * @param  array|QueryCriteria|Closure|Expression|string|int|Stringable|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  array  $columns  The columns to select
     * @return Collection<int|string, TModel>
     */
    public function findBy(array|QueryCriteria|Closure|Expression|string|int|Stringable|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): Collection;

    /**
     * Finds the first Eloquent model based on a set of conditions.
     *
     * @param  array|QueryCriteria|Closure|Expression|string|int|Stringable|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  array  $columns  The columns to select
     * @return TModel|null
     */
    public function findFirst(array|QueryCriteria|Closure|Expression|string|int|Stringable|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): ?Model;

    /**
     * Finds the first Eloquent model based on a set of conditions or throws an exception.
     *
     * @param  array|QueryCriteria|Closure|Expression|string|int|Stringable|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  array  $columns  The columns to select
     * @return TModel
     */
    public function findFirstOrFail(array|QueryCriteria|Closure|Expression|string|int|Stringable|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): Model;

    /**
     * Creates and persists a new Eloquent model.
     *
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(array $data): Model;

    /**
     * Updates an Eloquent model by its ID and returns the updated model or null if not found.
     *
     * @param  int|string  $id  The ID of the model to update
     * @param  array<string, mixed>  $data  The data to update
     * @param  array  $options  Additional options for the update operation
     * @return TModel|null
     */
    public function update(int|string $id, array $data, array $options = []): ?Model;

    /**
     * Deletes an Eloquent model by its ID.
     *
     * @param  int|string  $id  The ID of the model to delete
     */
    public function delete(int|string $id): bool;

    /**
     * Deletes a model instance.
     *
     * @param  TModel  $model  The model instance to delete
     */
    public function deleteModel(Model $model): bool;

    /**
     * Deletes models that match the given conditions.
     *
     * @param  array|QueryCriteria|Closure|Expression|string|int|Stringable|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @return int The number of records deleted
     */
    public function deleteBy(array|QueryCriteria|Closure|Expression|string|int|Stringable|null $conditions = null, ?Closure $queryModifier = null): int;

    /**
     * Process models in chunks.
     *
     * @param  int  $count  The number of models to retrieve per chunk
     * @param  Closure  $callback  The callback to process each chunk
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     */
    public function chunk(int $count, Closure $callback, ?Closure $queryModifier = null): void;

    /**
     * Check if models exist based on the given conditions.
     *
     * @param  array|QueryCriteria|Closure|Expression|string|int|Stringable|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  bool  $forceRaw  Whether to use the raw query builder
     */
    public function exists(array|QueryCriteria|Closure|Expression|string|int|Stringable|null $conditions = null, ?Closure $queryModifier = null, bool $forceRaw = false): bool;

    /**
     * Count models based on the given conditions.
     *
     * @param  array|QueryCriteria|Closure|Expression|string|int|Stringable|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  bool  $forceRaw  Whether to use the raw query builder
     */
    public function count(array|QueryCriteria|Closure|Expression|string|int|Stringable|null $conditions = null, ?Closure $queryModifier = null, bool $forceRaw = false): int;

    /**
     * Get a collection of a single column's values.
     *
     * @param  string  $column  The column to retrieve
     * @param  string|null  $key  The column to use as the collection keys
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  bool  $forceRaw  Whether to use the raw query builder
     */
    public function pluck(string $column, ?string $key = null, ?Closure $queryModifier = null, bool $forceRaw = false): Collection;

    /**
     * Returns an Eloquent Builder instance for flexible query building.
     *
     * @return EloquentBuilder<TModel>
     */
    public function query(): EloquentBuilder;

    /**
     * Returns a raw Query Builder instance using DB::table(...).
     */
    public function raw(): QueryBuilder;

    /**
     * Returns the fully qualified class name of the Eloquent model.
     *
     * @return class-string<TModel>
     */
    public function modelClass(): string;

    /**
     * Creates a new instance of the Eloquent model without persisting it.
     *
     * @return TModel
     */
    public function new(): Model;

    /**
     * Updates an existing model or creates a new one based on the provided attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values): Model;

    /**
     * Get the table name of the model.
     */
    public function getTable(): string;

    /**
     * Execute a callback within a database transaction.
     */
    public function transaction(Closure $callback): mixed;

    /**
     * Paginate the results using LengthAwarePaginator.
     */
    public function paginate(
        array|QueryCriteria|Closure|Expression|string|int|Stringable|null $conditions = null,
        ?Closure $queryModifier = null,
        array $params = []
    ): LengthAwarePaginator;

    /**
     * Paginate the results using SimplePaginator.
     */
    public function simplePaginate(
        array|QueryCriteria|Closure|Expression|string|int|Stringable|null $conditions = null,
        ?Closure $queryModifier = null,
        array $params = []
    ): Paginator;

    /**
     * Paginate the results using CursorPaginator.
     */
    public function cursorPaginate(
        array|QueryCriteria|Closure|Expression|string|int|Stringable|null $conditions = null,
        ?Closure $queryModifier = null,
        array $params = []
    ): CursorPaginator;

    /**
     * Run a query with a custom executor.
     */
    public function runQuery(
        array|QueryCriteria|Closure|Expression|string|int|Stringable|null $conditions = null,
        ?Closure $queryModifier = null,
        QueryExecutorInterface|Closure $executor = QueryExecutor::Get,
        array $params = []
    ): mixed;

    /**
     * Run a custom query using a closure.
     */
    public function runCustomQuery(Closure $closure): mixed;

    /**
     * Refresh only the given or loaded relations of the model.
     */
    public function refreshRelations(Model $model, ?array $relations = null): Model;

    /**
     * Refresh the model instance and optionally its relations.
     */
    public function refresh(Model $model, array|false|null $relations = null): Model;
}

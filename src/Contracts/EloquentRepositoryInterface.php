<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Contracts;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

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
     * @return TModel|null
     */
    public function find(int|string $id, array $columns = ['*']): ?Model;

    /**
     * Finds an Eloquent model by its ID or throws an exception.
     *
     * @param  int|string  $id  The ID of the model to find
     * @param  array  $columns  The columns to select
     * @return TModel
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    /**
     * Finds Eloquent models based on a set of conditions.
     *
     * @param  array|Closure|Expression|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  array  $columns  The columns to select
     * @return Collection<int|string, TModel>
     */
    public function findBy(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): Collection;

    /**
     * Finds the first Eloquent model based on a set of conditions.
     *
     * @param  array|Closure|Expression|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  array  $columns  The columns to select
     * @return TModel|null
     */
    public function findFirst(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): ?Model;

    /**
     * Finds the first Eloquent model based on a set of conditions or throws an exception.
     *
     * @param  array|Closure|Expression|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  array  $columns  The columns to select
     * @return TModel
     */
    public function findFirstOrFail(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): Model;

    /**
     * Returns a collection of all Eloquent models.
     *
     * @return Collection<int|string, TModel>
     */
    public function all(): Collection;

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
     * @param  array|Closure|Expression|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @return int The number of records deleted
     */
    public function deleteBy(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null): int;

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
     * @param  array|Closure|Expression|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  bool  $forceRaw  Whether to use the raw query builder
     */
    public function exists(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, bool $forceRaw = false): bool;

    /**
     * Count models based on the given conditions.
     *
     * @param  array|Closure|Expression|null  $conditions  Optional conditions to filter by
     * @param  Closure|null  $queryModifier  Optional callback to modify the query
     * @param  bool  $forceRaw  Whether to use the raw query builder
     */
    public function count(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, bool $forceRaw = false): int;

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
     * Collect executed queries for debugging purposes.
     *
     * @param  array|null  $collected  Reference to an array that will store the collected queries
     */
    public function withQueryCollection(?array &$collected = null): self;
}

# Eloquent Light Repository

A lightweight repository pattern implementation for Laravel Eloquent models.

## Compatibility

- Laravel 11.x
- Laravel 12.x (Future support)
- PHP 8.3 or higher

## Installation

You can install the package via composer:

```bash
composer require vaskiq/eloquent-light-repo
```

## Usage

### Create a Repository

Create a repository class for your model by extending the `EloquentRepository` abstract class:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Vaskiq\EloquentLightRepo\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends EloquentRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Implement the all() method required by the interface
     */
    public function all(): \Illuminate\Support\Collection
    {
        return $this->query()->get();
    }
}
```

### Use the Repository

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function index()
    {
        $users = $this->userRepository->all();

        return view('users.index', compact('users'));
    }

    public function show($id)
    {
        $user = $this->userRepository->findOrFail($id);

        return view('users.show', compact('user'));
    }

    public function store(Request $request)
    {
        try {
            $user = $this->userRepository->create($request->validated());
            return redirect()->route('users.show', $user->id);
        } catch (\Vaskiq\EloquentLightRepo\Exceptions\CreateException $e) {
            return back()->withErrors(['error' => 'Failed to create user']);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = $this->userRepository->update($id, $request->validated());
            return redirect()->route('users.show', $user->id);
        } catch (\Vaskiq\EloquentLightRepo\Exceptions\UpdateException $e) {
            return back()->withErrors(['error' => 'Failed to update user']);
        }
    }

    public function destroy($id)
    {
        if ($this->userRepository->delete($id)) {
            return redirect()->route('users.index')->with('success', 'User deleted successfully');
        }

        return back()->withErrors(['error' => 'User not found']);
    }
}
```

## Available Methods

- `find(int|string $id, array $columns = ['*']): ?Model` - Find a model by ID
- `findOrFail(int|string $id, array $columns = ['*']): Model` - Find a model by ID or throw an exception
- `findBy(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): Collection` - Find models by conditions
- `findFirst(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): ?Model` - Find first model by conditions
- `findFirstOrFail(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, array $columns = ['*']): Model` - Find first model by conditions or throw exception
- `all(): Collection` - Get all models (Note: This method is defined in the interface but must be implemented in your repository class)
- `create(array $data): Model` - Create a new model
- `update(int|string $id, array $data, array $options = []): ?Model` - Update a model
- `delete(int|string $id): bool` - Delete a model by ID
- `deleteModel(Model $model): bool` - Delete a model instance
- `deleteBy(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null): int` - Delete models by conditions
- `chunk(int $count, Closure $callback, ?Closure $queryModifier = null): void` - Process models in chunks
- `exists(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, bool $forceRaw = false): bool` - Check if models exist
- `count(array|Closure|Expression|null $conditions = null, ?Closure $queryModifier = null, bool $forceRaw = false): int` - Count models
- `pluck(string $column, ?string $key = null, ?Closure $queryModifier = null, bool $forceRaw = false): Collection` - Get a collection of column values
- `query(): EloquentBuilder` - Get a query builder instance
- `raw(): QueryBuilder` - Get a raw query builder instance
- `modelClass(): string` - Get the model class name
- `new(): Model` - Create a new model instance
- `updateOrCreate(array $attributes, array $values): Model` - Update or create a model
- `withQueryCollection(?array &$collected = null): self` - Collect executed queries for debugging

## Debugging

The package provides a method to collect and inspect executed queries:

```php
$queries = [];
$users = $userRepository->withQueryCollection($queries)->all();

// Now $queries contains all executed SQL queries with bindings and execution time
foreach ($queries as $query) {
    echo "SQL: {$query['sql']}\n";
    echo "Bindings: " . json_encode($query['bindings']) . "\n";
    echo "Execution time: {$query['time']}\n";
}
```

## Exceptions

The package provides the following exceptions:

- `CreateException` - Thrown when a model creation fails
- `UpdateException` - Thrown when a model update fails

Example of handling exceptions:

```php
try {
    $user = $this->userRepository->create($data);
} catch (\Vaskiq\EloquentLightRepo\Exceptions\CreateException $e) {
    // Handle the exception
    $originalException = $e->getPrevious(); // Get the original exception if needed
}

try {
    $user = $this->userRepository->update($id, $data);
} catch (\Vaskiq\EloquentLightRepo\Exceptions\UpdateException $e) {
    // Handle the exception
    $originalException = $e->getPrevious(); // Get the original exception if needed
}
```

## Testing

This package uses [Pest PHP](https://pestphp.com/) for testing. To run the tests:

```bash
composer test
```

For more information about testing, see the [tests/README.md](tests/README.md) file.

## License

The Apache License 2.0. Please see [License File](LICENSE.txt) for more information.
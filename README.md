# Eloquent Light Repository

Minimalistic base repository for Laravel Eloquent. Focuses on clean, flexible query structure and optional transactional logic.

## Features

- Unified interface for model queries
- Query conditions via arrays, closures, expressions, or `QueryCriteria`
- Built-in pagination (standard, simple, cursor)
- Optional transactions for write operations
- `runQuery()` for custom executions

## Installation

```bash
composer require vaskiq/eloquent-light-repo
```

## Usage

Extend the base repository:

```php
use Vaskiq\EloquentLightRepo\EloquentRepository;
use App\Models\User;

class UserRepository extends EloquentRepository
{
    public function __construct()
    {
        parent::__construct(new User());
    }
}
```

Query examples:

```php
$repo->find(1);
$repo->findBy(['active' => true]);
$repo->update(1, ['name' => 'New']);
$repo->delete(1);
$repo->paginate(null, fn ($q) => $q->orderBy('id', 'desc'));
```

## QueryCriteria

`QueryCriteria` allows expressive query conditions and modifications:

```php
use Vaskiq\EloquentLightRepo\Query\QueryCriteria;
use Vaskiq\EloquentLightRepo\Query\Conditions\Value;

$criteria = new QueryCriteria([
    'status' => Value::in(['active', 'pending']),
    'deleted_at' => null,
]);

$repo->findBy($criteria);
```

Also supports:
- primary key lookup: `new QueryCriteria(1)` or `new QueryCriteria([1, 2, 3])`
- nested conditions
- custom query modifier via closure: `new QueryCriteria([...], fn ($q) => ...)`

## API Overview

- `find($id)`
- `findBy(array|Closure|QueryCriteria|Expression)`
- `findFirst(...)`, `findFirstOrFail(...)`
- `update($id, array $data)`
- `delete($id)`
- `paginate(...)`, `simplePaginate(...)`, `cursorPaginate(...)`
- `exists(...)`, `count(...)`, `pluck(...)`
- `runQuery(...)`, `runCustomQuery(...)`
- `refresh($model)`, `refreshRelations($model)`

For full signatures, see the `EloquentRepository` class.

## License

Apache 2.0
See [LICENSE](LICENSE).
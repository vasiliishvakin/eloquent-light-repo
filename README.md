# Eloquent Light Repository

A lightweight base repository for Laravel Eloquent models.
Supports flexible query logic, optional transactions, and clean code structure.

## ✨ Features

- Simple and consistent Eloquent repository
- Supports closures and expressions for flexible conditions
- Built-in support for pagination (standard, simple, cursor)
- Optional transactions for updates and deletes
- `runQuery()` for custom query execution

## 📦 Installation

```bash
composer require vaskiq/eloquent-light-repo
```

## 🚀 Usage

Create your repository:

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

Basic methods:

```php
$repo->find(1);
$repo->findBy(['active' => true]);
$repo->update(1, ['name' => 'New Name']);
$repo->delete(1);
```

Advanced examples:

```php
// With custom query modifier
$repo->findFirst(fn ($q) => $q->where('email', 'like', '%@example.com'));

// Paginate with ordering
$repo->paginate(null, fn ($q) => $q->orderBy('created_at', 'desc'));
```

## 📘 API Highlights

- `find($id)`
- `findBy(array|Closure|Expression)`
- `update($id, array $data)`
- `delete($id)`
- `paginate()`, `simplePaginate()`, `cursorPaginate()`
- `runQuery()` — for custom execution
- `refresh($model)` and `refreshRelations($model)`


> **📎 Tip:** For a full list of available methods and signatures, explore the `EloquentRepository` class directly in the source code.

## 📄 License

Apache License 2.0
See [LICENSE](LICENSE) for details.
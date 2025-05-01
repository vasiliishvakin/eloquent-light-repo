<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Mockery as m;
use Vaskiq\EloquentLightRepo\EloquentRepository;

beforeEach(function () {
    $this->mockQueryBuilder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
    $this->mockModel = m::mock('Illuminate\\Database\\Eloquent\\Model');

    $this->mockModel->shouldReceive('newModelQuery')->andReturn($this->mockQueryBuilder);
    $this->mockModel->shouldReceive('getConnection')->andReturnSelf();
    $this->mockModel->shouldReceive('getTable')->andReturn('test_table');

    $this->mockQueryBuilder->shouldReceive('where')->with(m::any())->andReturnSelf();

    $this->repository = new class($this->mockModel) extends EloquentRepository
    {
        public function __construct(Model $model)
        {
            parent::__construct($model);
        }
    };
});

test('find returns model instance', function () {
    $this->mockQueryBuilder->shouldReceive('whereKey')->with(1)->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('first')->with(['*'])->andReturn($this->mockModel);

    expect($this->repository->find(1))->toBe($this->mockModel);
});

test('find applies queryModifier closure', function () {
    $this->mockQueryBuilder->shouldReceive('where')->with(['active' => 1])->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('whereKey')->with(1)->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('first')->with(['*'])->andReturn($this->mockModel);

    $modifier = fn ($query) => $query->where(['active' => 1]);

    expect($this->repository->find(1, $modifier))->toBe($this->mockModel);
});

test('findOrFail throws an exception if model not found', function () {
    $this->mockQueryBuilder->shouldReceive('whereKey')->with(999)->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('firstOrFail')->with(['*'])->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException);

    $this->repository->findOrFail(999);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('findOrFail applies queryModifier closure', function () {
    $this->mockQueryBuilder->shouldReceive('where')->with(['active' => 1])->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('whereKey')->with(1)->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('firstOrFail')->with(['*'])->andReturn($this->mockModel);

    $modifier = fn ($query) => $query->where(['active' => 1]);

    expect($this->repository->findOrFail(1, $modifier))->toBe($this->mockModel);
});

test('create throws exception on failure', function () {
    $this->mockModel->shouldReceive('create')->andThrow(new Exception('DB error'));

    $this->repository->create(['foo' => 'bar']);
})->throws(Exception::class);

test('update throws exception on failure', function () {
    $this->mockQueryBuilder->shouldReceive('whereKey')->with(1)->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('first')->with(['*'])->andReturn($this->mockModel);
    $this->mockModel->shouldReceive('update')->andThrow(new Exception('Update error'));

    $this->repository->update(1, ['title' => 'updated']);
})->throws(Exception::class);

test('delete returns true when model deleted successfully', function () {
    $this->mockQueryBuilder->shouldReceive('whereKey')->with(1)->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('first')->with(['*'])->andReturn($this->mockModel);
    $this->mockModel->shouldReceive('delete')->andReturn(true);

    expect($this->repository->delete(1))->toBeTrue();
});

test('delete returns false when model not found', function () {
    $this->mockQueryBuilder->shouldReceive('whereKey')->with(1)->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('first')->with(['*'])->andReturn(null);

    expect($this->repository->delete(1))->toBeFalse();
});

test('count returns expected value', function () {
    $this->mockQueryBuilder->shouldReceive('count')->andReturn(42);

    expect($this->repository->count())->toBe(42);
});

test('pluck returns expected collection', function () {
    $collection = new Collection(['one', 'two']);
    $this->mockQueryBuilder->shouldReceive('pluck')->with('name', null)->andReturn($collection);

    expect($this->repository->pluck('name'))->toBe($collection);
});

test('getTable returns correct table name', function () {
    expect($this->repository->getTable())->toBe('test_table');
});

test('transaction executes callback within transaction', function () {
    $this->mockModel->shouldReceive('transaction')->andReturnUsing(fn ($callback) => $callback());

    $result = $this->repository->transaction(fn () => 'ok');

    expect($result)->toBe('ok');
});

test('refreshRelations refreshes specific relations', function () {
    $this->mockModel->shouldReceive('unsetRelation')->with('posts');
    $this->mockModel->shouldReceive('load')->with(['posts'])->andReturnSelf();

    $result = $this->repository->refreshRelations($this->mockModel, ['posts']);

    expect($result)->toBe($this->mockModel);
});

test('refresh refreshes model and relations', function () {
    $this->mockModel->shouldReceive('refresh');
    $this->mockModel->shouldReceive('getRelations')->andReturn(['comments' => 'dummy']);
    $this->mockModel->shouldReceive('unsetRelation')->with('comments');
    $this->mockModel->shouldReceive('load')->with(['comments'])->andReturnSelf();

    $result = $this->repository->refresh($this->mockModel, null);

    expect($result)->toBe($this->mockModel);
});

test('paginate returns LengthAwarePaginator', function () {
    $items = new Collection([1, 2, 3]);
    $paginator = new LengthAwarePaginator($items, 3, 15, 1);

    $this->mockQueryBuilder->shouldReceive('paginate')->with(15)->andReturn($paginator);

    $result = $this->repository->paginate(null, null, [15]);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->items())->toEqual([1, 2, 3]);
});

test('simplePaginate returns Paginator', function () {
    $items = new Collection([1, 2]);
    $paginator = new Paginator($items, 10, 1);

    $this->mockQueryBuilder->shouldReceive('simplePaginate')->with(10)->andReturn($paginator);

    $result = $this->repository->simplePaginate(null, null, [10]);

    expect($result)->toBeInstanceOf(Paginator::class);
});

test('cursorPaginate returns CursorPaginator', function () {
    $items = new Collection([1, 2]);
    $cursor = new Cursor([], true); // <-- fix здесь
    $paginator = new CursorPaginator($items, 20, $cursor);

    $this->mockQueryBuilder->shouldReceive('cursorPaginate')->with(20)->andReturn($paginator);

    $result = $this->repository->cursorPaginate(null, null, [20]);

    expect($result)->toBeInstanceOf(CursorPaginator::class);
});

test('deleteBy deletes matching records', function () {
    $this->mockQueryBuilder->shouldReceive('where')->with(['status' => 'inactive'])->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('delete')->andReturn(3);

    $deletedCount = $this->repository->deleteBy(['status' => 'inactive']);

    expect($deletedCount)->toBe(3);
});

test('exists returns true when records found', function () {
    $this->mockQueryBuilder->shouldReceive('where')->with(['email' => 'test@example.com'])->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('exists')->andReturn(true);

    $exists = $this->repository->exists(['email' => 'test@example.com']);

    expect($exists)->toBeTrue();
});

test('exists returns false when no records found', function () {
    $this->mockQueryBuilder->shouldReceive('where')->with(['email' => 'none@example.com'])->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('exists')->andReturn(false);

    $exists = $this->repository->exists(['email' => 'none@example.com']);

    expect($exists)->toBeFalse();
});

test('chunk processes models in chunks', function () {
    $model1 = m::mock(Model::class);
    $model2 = m::mock(Model::class);

    $this->mockQueryBuilder->shouldReceive('chunk')
        ->with(2, m::on(function ($callback) use ($model1, $model2) {
            $callback(collect([$model1, $model2]));

            return true;
        }));

    $processed = [];

    $this->repository->chunk(2, function ($chunk) use (&$processed) {
        $processed = $chunk->all();
    });

    expect($processed)->toHaveCount(2);
});

test('findBy returns collection of models', function () {
    $collection = new Collection([$this->mockModel, $this->mockModel]);

    $this->mockQueryBuilder->shouldReceive('where')->with(['active' => true])->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('get')->with(['*'])->andReturn($collection);

    $result = $this->repository->findBy(['active' => true]);

    expect($result)->toBe($collection);
});

test('findFirst returns first matching model', function () {
    $this->mockQueryBuilder->shouldReceive('where')->with(['email' => 'test@example.com'])->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('first')->with(['*'])->andReturn($this->mockModel);

    $result = $this->repository->findFirst(['email' => 'test@example.com']);

    expect($result)->toBe($this->mockModel);
});

test('findFirstOrFail returns first matching model or fails', function () {
    $this->mockQueryBuilder->shouldReceive('where')->with(['id' => 10])->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('firstOrFail')->with(['*'])->andReturn($this->mockModel);

    $result = $this->repository->findFirstOrFail(['id' => 10]);

    expect($result)->toBe($this->mockModel);
});

test('raw returns raw query builder', function () {
    $rawBuilder = m::mock('Illuminate\\Database\\Query\\Builder');

    $connection = m::mock('Illuminate\\Database\\Connection');
    $connection->shouldReceive('table')->with('test_table')->andReturn($rawBuilder);

    $model = m::mock('Illuminate\\Database\\Eloquent\\Model');
    $model->shouldReceive('getConnection')->andReturn($connection);
    $model->shouldReceive('getTable')->andReturn('test_table');

    $repository = new class($model) extends EloquentRepository
    {
        public function __construct($model)
        {
            parent::__construct($model);
        }
    };

    expect($repository->raw())->toBe($rawBuilder);
});

test('new returns new model instance', function () {
    $this->mockModel->shouldReceive('getConnection')->andReturnSelf(); // for other internal calls
    $this->mockModel->shouldReceive('getTable')->andReturn('fake');

    $modelClass = get_class($this->mockModel);

    app()->bind($modelClass, fn () => $this->mockModel);

    $repo = new class($this->mockModel) extends EloquentRepository
    {
        public function __construct($model)
        {
            parent::__construct($model);
        }
    };

    expect($repo->new())->toBe($this->mockModel);
});

test('updateOrCreate updates existing or creates new model', function () {
    $expected = m::mock(Model::class);

    $this->mockQueryBuilder->shouldReceive('updateOrCreate')
        ->with(['email' => 'a@example.com'], ['name' => 'A'])
        ->andReturn($expected);

    $result = $this->repository->updateOrCreate(['email' => 'a@example.com'], ['name' => 'A']);

    expect($result)->toBe($expected);
});

test('runCustomQuery executes closure against query', function () {
    $this->mockQueryBuilder->shouldReceive('where')->with('foo', 'bar')->andReturnSelf();
    $this->mockQueryBuilder->shouldReceive('count')->andReturn(42);

    $result = $this->repository->runCustomQuery(function ($query) {
        return $query->where('foo', 'bar')->count();
    });

    expect($result)->toBe(42);
});

test('constructor assigns defaultQueryClosure', function () {
    $called = false;
    $closure = function ($query) use (&$called) {
        $called = true;
        $query->where('active', true);
    };

    $builder = m::mock('Illuminate\\Database\\Eloquent\\Builder');
    $builder->shouldReceive('where')->with('active', true)->once();

    $model = m::mock('Illuminate\\Database\\Eloquent\\Model');
    $model->shouldReceive('newModelQuery')->andReturn($builder);

    $repo = new class($model, $closure) extends EloquentRepository
    {
        public function __construct($model, $closure)
        {
            parent::__construct($model, $closure);
        }
    };

    // триггерим defaultQueryClosure через ->query()
    $repo->query();

    expect($called)->toBeTrue();
});

test('modelClass returns correct class name', function () {
    $model = new class extends Model
    {
        protected $table = 'test';
    };

    $repo = new class($model) extends EloquentRepository
    {
        public function __construct($model)
        {
            parent::__construct($model);
        }
    };

    expect($repo->modelClass())->toBe($model::class);
});

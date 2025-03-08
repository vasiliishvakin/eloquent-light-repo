<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mockery as m;
use Vaskiq\EloquentLightRepo\EloquentRepository;
use Vaskiq\EloquentLightRepo\Exceptions\CreateException;
use Vaskiq\EloquentLightRepo\Exceptions\UpdateException;

beforeEach(function () {
    $this->mockQueryBuilder = m::mock(\Illuminate\Database\Eloquent\Builder::class);
    $this->mockModel = m::mock(Model::class);

    $this->mockModel->shouldReceive('newModelQuery')
        ->andReturn($this->mockQueryBuilder);

    $this->repository = new class($this->mockModel) extends EloquentRepository
    {
        public function __construct(Model $model)
        {
            parent::__construct($model);
        }

        public function all(): \Illuminate\Support\Collection
        {
            return collect([]);
        }
    };
});

test('find returns model instance', function () {
    $this->mockQueryBuilder->shouldReceive('find')->with(1, ['*'])->andReturn($this->mockModel);

    expect($this->repository->find(1))->toBe($this->mockModel);
});

test('findOrFail throws an exception if model not found', function () {
    $this->mockQueryBuilder->shouldReceive('findOrFail')->with(999, ['*'])->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException);

    $this->repository->findOrFail(999);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('create throws CreateException on failure', function () {
    $this->mockModel->shouldReceive('create')->andThrow(new Exception('Database error'));

    $this->repository->create(['title' => 'Test Note']);
})->throws(CreateException::class);

test('update throws UpdateException on failure', function () {
    $this->mockQueryBuilder->shouldReceive('find')->with(1, m::any())->andReturn($this->mockModel);
    $this->mockModel->shouldReceive('update')->andThrow(new Exception('Update error'));
    $this->mockModel->shouldReceive('getConnection')->andReturnSelf();

    $this->repository->update(1, ['title' => 'Updated Title']);
})->throws(UpdateException::class);

test('delete returns true when model deleted successfully', function () {
    $this->mockQueryBuilder->shouldReceive('find')->with(1, ['*'])->andReturn($this->mockModel);
    $this->mockModel->shouldReceive('delete')->andReturn(true);

    expect($this->repository->delete(1))->toBeTrue();
});

test('delete returns false when model not found', function () {
    $this->mockQueryBuilder->shouldReceive('find')->with(1, ['*'])->andReturn(null);

    expect($this->repository->delete(1))->toBeFalse();
});

test('count returns expected value', function () {
    $this->mockQueryBuilder->shouldReceive('count')->andReturn(10);

    expect($this->repository->count())->toBe(10);
});

test('pluck returns expected collection', function () {
    $collection = new Collection(['title1', 'title2']);

    $this->mockQueryBuilder->shouldReceive('pluck')->with('title', null)->andReturn($collection);

    expect($this->repository->pluck('title'))->toBe($collection);
});

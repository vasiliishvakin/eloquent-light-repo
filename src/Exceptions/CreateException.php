<?php

declare(strict_types=1);

namespace Vaskiq\EloquentLightRepo\Exceptions;

use Exception;

class CreateException extends Exception
{
    public static function failed(string $modelClass, ?\Throwable $previous = null): self
    {
        return new self("Failed to create model: {$modelClass}", 0, $previous);
    }
}

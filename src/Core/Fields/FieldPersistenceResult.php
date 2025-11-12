<?php

namespace Monstrex\Ave\Core\Fields;

use Closure;

/**
 * Encapsulates the result of field persistence preparation.
 *
 * Carries the normalized value that should be written to the data source,
 * along with any deferred actions that must be executed after the model
 * has been saved.
 */
class FieldPersistenceResult
{
    /**
     * @param  mixed  $value
     * @param  array<int,Closure>  $deferred
     * @param  bool  $shouldPersist
     */
    public function __construct(
        protected mixed $value,
        protected array $deferred = [],
        protected bool $shouldPersist = true,
    ) {}

    public static function make(mixed $value, array $deferred = [], bool $shouldPersist = true): self
    {
        return new self($value, $deferred, $shouldPersist);
    }

    public static function empty(): self
    {
        return new self(null, [], false);
    }

    public static function skip(array $deferred = []): self
    {
        return new self(null, $deferred, false);
    }

    public function value(): mixed
    {
        return $this->value;
    }

    /**
     * @return array<int,Closure>
     */
    public function deferredActions(): array
    {
        return $this->deferred;
    }

    public function shouldPersist(): bool
    {
        return $this->shouldPersist;
    }
}

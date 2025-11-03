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
     */
    public function __construct(
        protected mixed $value,
        protected array $deferred = [],
    ) {}

    public static function make(mixed $value, array $deferred = []): self
    {
        return new self($value, $deferred);
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
}

<?php

namespace Monstrex\Ave\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Contract for fields that can interpret stored values coming from nested containers.
 *
 * Allows containers (e.g. Fieldset) to pass raw stored data back to the field without
 * relying on concrete field implementations.
 */
interface HandlesNestedValue
{
    /**
     * Apply nested stored value so the field can restore its state.
     *
     * @param mixed $storedValue Value stored inside the container (string/array/etc).
     * @param Model|null $record Parent record currently being edited (if any).
     */
    public function applyNestedValue(mixed $storedValue, ?Model $record = null): void;
}


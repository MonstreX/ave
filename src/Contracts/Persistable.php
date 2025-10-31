<?php

namespace Monstrex\Ave\Contracts;

/**
 * Persistable Contract
 * Defines the interface for persistent models
 */
interface Persistable
{
    /**
     * Create a new model instance from data
     *
     * @param array $data Raw data
     * @return mixed Created model
     */
    public static function create(array $data);

    /**
     * Update an existing model instance
     *
     * @param array $data Raw data
     * @return bool Success status
     */
    public function update(array $data): bool;

    /**
     * Delete the model instance
     *
     * @return bool Success status
     */
    public function delete(): bool;
}

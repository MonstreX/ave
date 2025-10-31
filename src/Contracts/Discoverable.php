<?php

namespace Monstrex\Ave\Contracts;

/**
 * Discoverable Contract
 * Defines the interface for discoverable components
 */
interface Discoverable
{
    /**
     * Get unique slug for this entity
     *
     * @return string
     */
    public static function getSlug(): string;

    /**
     * Get display label for this entity
     *
     * @return string
     */
    public static function getLabel(): string;
}

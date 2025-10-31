<?php

namespace Monstrex\Ave\Contracts;

/**
 * Renderable Contract
 * Defines the interface for renderable components
 */
interface Renderable
{
    /**
     * Render component and return payload for view
     *
     * @param mixed $context Request context
     * @return array View payload
     */
    public static function render($context): array;
}

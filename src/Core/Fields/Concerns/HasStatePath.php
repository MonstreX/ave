<?php

namespace Monstrex\Ave\Core\Fields\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * HasStatePath trait provides compositional state path building.
 *
 * Instead of parsing HTML keys post-hoc, this trait builds state paths
 * by composing with parent containers top-down, similar to Filament's
 * approach. This ensures deterministic, predictable paths.
 *
 * Examples:
 * - Simple field: 'name' -> getStatePath() = 'name'
 * - In fieldset: 'title' in 'items' -> getStatePath() = 'items.title'
 * - Deeply nested: 'image' in 'sections[0]' -> getStatePath() = 'sections.0.image'
 */
trait HasStatePath
{
    protected ?string $statePath = null;

    /**
     * Set the absolute state path for this component.
     * Typically used for non-nested fields or explicit override.
     *
     * @param string|null $path The state path (e.g., 'profile.avatar')
     */
    public function statePath(?string $path): static
    {
        $clone = clone $this;
        $clone->statePath = $path;

        return $clone;
    }

    /**
     * Get the absolute state path for this component.
     *
     * Builds compositionally: parent path prefix + '.' + own key
     *
     * Resolution order:
     * 1. If explicitly set via statePath(), return that
     * 2. If container exists and implements getChildStatePath(), compose with parent
     * 3. Otherwise, use baseKey (root field)
     */
    public function getStatePath(): string
    {
        // If explicitly set, use it
        if ($this->statePath !== null) {
            return $this->statePath;
        }

        // Try to compose with parent container
        if ($this->container && method_exists($this->container, 'getChildStatePath')) {
            try {
                $parentPath = $this->container->getChildStatePath();

                // Empty parent path means root level
                if (!empty($parentPath)) {
                    return "{$parentPath}.{$this->baseKey()}";
                }
            } catch (\Exception $e) {
                // If parent method fails, fall back to own key
                Log::warning('Failed to get child state path from parent container', [
                    'parent_class' => get_class($this->container),
                    'field_key' => $this->baseKey(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Root level field (no container or container has no path)
        return $this->baseKey();
    }

    /**
     * Get state path for child components.
     *
     * Containers override this to provide their state path as the prefix
     * for children. Non-container fields return their own path.
     *
     * This method is called by children to determine their parent's path prefix.
     */
    public function getChildStatePath(): string
    {
        return $this->getStatePath();
    }
}

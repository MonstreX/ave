<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * Tags Field
 *
 * A field for entering multiple tags/labels as comma-separated values or chips.
 *
 * Features:
 * - Multiple tag input with chip display
 * - Comma-separated value parsing
 * - Optional tag suggestions/autocomplete
 * - Trimming and duplicate handling
 * - Flexible separator support
 *
 * Example (Basic):
 *   Tags::make('tags')
 *       ->label('Article Tags')
 *       ->separator(',')
 *
 * Example (With suggestions):
 *   Tags::make('keywords')
 *       ->label('Keywords')
 *       ->suggestions(['laravel', 'php', 'javascript', 'vue', 'react'])
 *
 * Example (Custom separator):
 *   Tags::make('categories')
 *       ->label('Categories')
 *       ->separator(';')
 *       ->allowDuplicates(false)
 */
class Tags extends AbstractField
{
    /**
     * Separator character(s) for parsing tags
     */
    protected string $separator = ',';

    /**
     * Allow duplicate tags
     */
    protected bool $allowDuplicates = false;

    /**
     * Suggested tags for autocomplete
     */
    protected array $suggestions = [];

    /**
     * Set separator for tag parsing
     *
     * @param string $separator Separator character (comma, semicolon, space, etc.)
     * @return static
     */
    public function separator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Get separator
     *
     * @return string
     */
    public function getSeparator(): string
    {
        return $this->separator;
    }

    /**
     * Allow or disallow duplicate tags
     *
     * @param bool $allow Whether to allow duplicates
     * @return static
     */
    public function allowDuplicates(bool $allow = true): static
    {
        $this->allowDuplicates = $allow;
        return $this;
    }

    /**
     * Check if duplicates are allowed
     *
     * @return bool
     */
    public function allowsDuplicates(): bool
    {
        return $this->allowDuplicates;
    }

    /**
     * Set tag suggestions for autocomplete
     *
     * @param array $suggestions Array of suggested tag strings
     * @return static
     */
    public function suggestions(array $suggestions): static
    {
        $this->suggestions = $suggestions;
        return $this;
    }

    /**
     * Get tag suggestions
     *
     * @return array
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'separator'       => $this->separator,
            'allowDuplicates' => $this->allowDuplicates,
            'suggestions'     => $this->suggestions,
        ]);
    }

    /**
     * Extract and parse tag values from input
     *
     * Parses comma-separated or custom-separated string into array of tags
     *
     * @param mixed $raw Raw input value (string or array)
     * @return array|null Array of tags or null if empty
     */
    public function extract(mixed $raw): mixed
    {
        if ($raw === null || $raw === '' || (is_array($raw) && empty($raw))) {
            return null;
        }

        // If already an array, work with it
        if (is_array($raw)) {
            $tags = $raw;
        } else {
            // Parse by separator
            $tags = explode($this->separator, (string)$raw);
        }

        // Trim whitespace from each tag
        $tags = array_map('trim', $tags);

        // Remove empty tags
        $tags = array_filter($tags, fn ($tag) => !empty($tag));

        // Remove duplicates if not allowed
        if (!$this->allowDuplicates) {
            $tags = array_unique(array_map('strtolower', $tags));
        }

        // Re-index array
        $tags = array_values($tags);

        return !empty($tags) ? $tags : null;
    }
}

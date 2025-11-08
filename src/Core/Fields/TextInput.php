<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * TextInput Field
 *
 * A basic text input field for single-line text data.
 *
 * Features:
 * - Minimum and maximum length constraints
 * - Regular expression pattern validation
 * - HTML input type: text
 *
 * Example:
 *   TextInput::make('username')
 *       ->label('Username')
 *       ->required()
 *       ->minLength(3)
 *       ->maxLength(50)
 *       ->pattern('^[a-zA-Z0-9_]+$')
 */
class TextInput extends AbstractField
{
    /**
     * Maximum allowed length in characters
     */
    protected ?string $maxLength = null;

    /**
     * Minimum required length in characters
     */
    protected ?string $minLength = null;

    /**
     * Regular expression pattern for validation
     */
    protected ?string $pattern = null;

    /**
     * Set maximum allowed length
     *
     * @param int $length Maximum number of characters allowed
     * @return static
     */
    public function maxLength(int $length): static
    {
        $this->maxLength = $length;
        return $this;
    }

    /**
     * Set minimum required length
     *
     * @param int $length Minimum number of characters required
     * @return static
     */
    public function minLength(int $length): static
    {
        $this->minLength = $length;
        return $this;
    }

    /**
     * Set regular expression pattern for validation
     *
     * @param string $pattern Regular expression pattern (e.g., '^[a-zA-Z0-9_]+$')
     * @return static
     */
    public function pattern(string $pattern): static
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data with maxLength, minLength, and pattern
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'maxLength' => $this->maxLength,
            'minLength' => $this->minLength,
            'pattern'   => $this->pattern,
        ]);
    }
}

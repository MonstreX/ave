<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * Textarea Field
 *
 * A multi-line text input field for longer text content.
 *
 * Features:
 * - Configurable number of rows
 * - Maximum length constraint
 * - HTML textarea element with optional character limit
 *
 * Example:
 *   Textarea::make('description')
 *       ->label('Description')
 *       ->rows(5)
 *       ->maxLength(1000)
 *       ->required()
 */
class Textarea extends AbstractField
{
    /**
     * Number of visible rows in the textarea
     */
    protected ?int $rows = null;

    /**
     * Maximum allowed character length
     */
    protected ?int $maxLength = null;

    /**
     * Set number of visible rows
     *
     * @param int $rows Number of rows to display
     * @return static
     */
    public function rows(int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * Set maximum allowed character length
     *
     * @param int $length Maximum number of characters
     * @return static
     */
    public function maxLength(int $length): static
    {
        $this->maxLength = $length;
        return $this;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data with rows and maxLength
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'rows'      => $this->rows,
            'maxLength' => $this->maxLength,
        ]);
    }
}

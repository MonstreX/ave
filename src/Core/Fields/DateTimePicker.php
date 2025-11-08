<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * DateTimePicker Field
 *
 * A date and/or time picker field with optional time component.
 *
 * Features:
 * - Date and time selection with calendar picker
 * - Optional time component (can be date-only)
 * - Configurable date format
 * - Minimum and maximum date constraints
 * - Uses Flatpickr library for cross-browser compatibility
 *
 * Example:
 *   DateTimePicker::make('event_date')
 *       ->label('Event Date')
 *       ->withTime(true)
 *       ->minDate('2024-01-01')
 *       ->maxDate('2024-12-31')
 *
 *   DateTimePicker::make('birth_date')
 *       ->label('Birth Date')
 *       ->withTime(false)
 *       ->format('Y-m-d')
 */
class DateTimePicker extends AbstractField
{
    /**
     * Date/time format string for display and parsing
     * Default: 'Y-m-d H:i:s' (with time) or 'Y-m-d' (date only)
     */
    protected string $format = 'Y-m-d H:i:s';

    /**
     * Whether to include time component in picker
     */
    protected bool $withTime = true;

    /**
     * Minimum selectable date (YYYY-MM-DD format)
     */
    protected ?string $minDate = null;

    /**
     * Maximum selectable date (YYYY-MM-DD format)
     */
    protected ?string $maxDate = null;

    /**
     * Set date/time format
     *
     * Uses PHP date format string (e.g., 'Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i')
     *
     * @param string $format PHP date format string
     * @return static
     */
    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Include/exclude time component in picker
     *
     * When set to false, automatically changes format to 'Y-m-d'
     *
     * @param bool $withTime Whether to show time picker
     * @return static
     */
    public function withTime(bool $withTime = true): static
    {
        $this->withTime = $withTime;
        if (!$withTime) {
            $this->format = 'Y-m-d';
        }
        return $this;
    }

    /**
     * Set minimum selectable date
     *
     * @param string $date Date in YYYY-MM-DD format
     * @return static
     */
    public function minDate(string $date): static
    {
        $this->minDate = $date;
        return $this;
    }

    /**
     * Set maximum selectable date
     *
     * @param string $date Date in YYYY-MM-DD format
     * @return static
     */
    public function maxDate(string $date): static
    {
        $this->maxDate = $date;
        return $this;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data with format, withTime, minDate, and maxDate
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'format'   => $this->format,
            'withTime' => $this->withTime,
            'minDate'  => $this->minDate,
            'maxDate'  => $this->maxDate,
        ]);
    }

    /**
     * Extract date/time value, returning null for empty input
     *
     * @param mixed $raw Raw input value
     * @return string|null Extracted value or null if empty
     */
    public function extract(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        return $raw;
    }
}

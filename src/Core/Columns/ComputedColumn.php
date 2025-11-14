<?php

namespace Monstrex\Ave\Core\Columns;

use Closure;

/**
 * ComputedColumn - virtual column computed from record data via closure.
 * 
 * Use cases:
 * - Display calculated values from multiple fields
 * - Format complex data combinations
 * - Show derived properties not stored in database
 * 
 * Example:
 * ComputedColumn::make('full_name')
 *     ->label('Full Name')
 *     ->compute(fn($record) => "{$record->first_name} {$record->last_name}")
 *     ->html()
 * 
 * ComputedColumn::make('status_badge')
 *     ->compute(function($record) {
 *         $color = $record->is_active ? 'success' : 'danger';
 *         $label = $record->is_active ? 'Active' : 'Inactive';
 *         return "<span class=\"badge badge-{$color}\">{$label}</span>";
 *     })
 *     ->html()
 */
class ComputedColumn extends Column
{
    protected string $type = 'computed';
    protected ?Closure $computeCallback = null;

    public static function make(string $key): static
    {
        return new static($key);
    }

    /**
     * Set computation callback that receives the full record.
     * 
     * @param Closure $callback Receives ($record) and returns computed value
     */
    public function compute(Closure $callback): static
    {
        $this->computeCallback = $callback;
        return $this;
    }

    /**
     * Resolve value - always computed from record, ignoring actual DB column.
     */
    public function resolveRecordValue(mixed $record): mixed
    {
        if ($this->computeCallback === null) {
            return null;
        }

        return call_user_func($this->computeCallback, $record);
    }

    /**
     * Format the computed value.
     * Applies parent formatting (custom format callback, escaping, etc.)
     */
    public function formatValue(mixed $value, mixed $record): mixed
    {
        // Apply parent formatting if custom format callback is set
        if ($this->formatCallback !== null) {
            return parent::formatValue($value, $record);
        }

        // Otherwise return computed value as-is (with escaping if enabled)
        return $this->escape ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : $value;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'computed' => true,
        ]);
    }
}

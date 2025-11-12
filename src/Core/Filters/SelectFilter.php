<?php

namespace Monstrex\Ave\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class SelectFilter extends Filter
{
    protected array $options = [];
    protected bool $multiple = false;

    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        if (!$value) {
            return $query;
        }

        if ($this->multiple && is_string($value)) {
            $value = explode(',', $value);
        }

        if (is_array($value)) {
            return $query->whereIn($this->key, $value);
        }

        return $query->where($this->key, '=', $value);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options'  => $this->options,
            'multiple' => $this->multiple,
        ]);
    }

    public function formatBadgeValue(mixed $value): string
    {
        $format = function ($single) {
            return $this->options[$single] ?? (string) $single;
        };

        if (is_array($value)) {
            return implode(', ', array_map($format, $value));
        }

        return $format($value);
    }
}

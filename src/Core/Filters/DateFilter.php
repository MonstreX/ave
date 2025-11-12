<?php

namespace Monstrex\Ave\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class DateFilter extends Filter
{
    protected string $operator = '=';
    protected string $format = 'Y-m-d';

    public function operator(string $op): static
    {
        $this->operator = $op;
        return $this;
    }

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        if (!$value) {
            return $query;
        }

        // If value is range (from/to)
        if (is_array($value)) {
            if (isset($value['from']) && $value['from']) {
                $query->whereDate($this->key, '>=', $value['from']);
            }
            if (isset($value['to']) && $value['to']) {
                $query->whereDate($this->key, '<=', $value['to']);
            }
            return $query;
        }

        return $query->whereDate($this->key, $this->operator, $value);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'operator' => $this->operator,
            'format'   => $this->format,
        ]);
    }

    public function formatBadgeValue(mixed $value): string
    {
        if (is_array($value)) {
            $from = $value['from'] ?? null;
            $to = $value['to'] ?? null;

            if ($from && $to) {
                return "{$from} → {$to}";
            }

            if ($from) {
                return "from {$from}";
            }

            if ($to) {
                return "to {$to}";
            }
        }

        return (string) $value;
    }
}

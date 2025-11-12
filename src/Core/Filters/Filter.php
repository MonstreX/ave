<?php

namespace Monstrex\Ave\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

abstract class Filter
{
    protected string $key;
    protected ?string $label = null;
    protected mixed $default = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;
        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label ?? ucfirst(str_replace('_', ' ', $this->key));
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Convert value to display string for badges.
     */
    public function formatBadgeValue(mixed $value): string
    {
        if (is_array($value)) {
            $clean = array_map(
                fn ($item) => is_scalar($item) ? (string) $item : json_encode($item),
                $value
            );

            return implode(', ', $clean);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    /**
     * Apply filter to query
     */
    abstract public function apply(Builder $query, mixed $value): Builder;

    public function toArray(): array
    {
        return [
            'key'     => $this->key,
            'label'   => $this->getLabel(),
            'default' => $this->default,
        ];
    }
}

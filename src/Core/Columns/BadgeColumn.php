<?php

namespace Monstrex\Ave\Core\Columns;

class BadgeColumn extends Column
{
    protected string $type = 'badge';
    protected array $colorMap = [];
    protected array $iconMap = [];
    protected string $defaultColor = 'info';
    protected bool $uppercase = false;
    protected bool $pill = false;

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function colors(array $map, ?string $default = null): static
    {
        $this->colorMap = $map;
        if ($default !== null) {
            $this->defaultColor = $default;
        }
        return $this;
    }

    public function icons(array $map): static
    {
        $this->iconMap = $map;
        return $this;
    }

    public function uppercase(bool $on = true): static
    {
        $this->uppercase = $on;
        return $this;
    }

    public function pill(bool $on = true): static
    {
        $this->pill = $on;
        return $this;
    }

    public function resolveColor(mixed $value): string
    {
        $key = (string) $value;
        return $this->colorMap[$key] ?? $this->defaultColor;
    }

    public function resolveIcon(mixed $value): ?string
    {
        $key = (string) $value;
        return $this->iconMap[$key] ?? null;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'defaultColor' => $this->defaultColor,
            'uppercase' => $this->uppercase,
            'pill' => $this->pill,
        ]);
    }

    public function isUppercase(): bool
    {
        return $this->uppercase;
    }

    public function isPill(): bool
    {
        return $this->pill;
    }
}


<?php

namespace Monstrex\Ave\Core\Columns;

use Illuminate\Support\Str;

class TextColumn extends Column
{
    protected string $type = 'text';
    protected ?int $limit = null;
    protected bool $uppercase = false;
    protected ?string $suffix = null;

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function limit(int $characters): static
    {
        $this->limit = $characters;
        return $this;
    }

    public function uppercase(bool $on = true): static
    {
        $this->uppercase = $on;
        return $this;
    }

    public function suffix(?string $suffix): static
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function linkToEdit(array $params = []): static
    {
        parent::linkToEdit($params);
        return $this;
    }

    public function formatValue(mixed $value, mixed $record): mixed
    {
        $value = parent::formatValue($value, $record);

        if (is_string($value)) {
            $value = $this->uppercase ? Str::upper($value) : $value;
            if ($this->limit !== null) {
                $value = Str::limit($value, $this->limit);
            }
            if ($this->suffix) {
                $value .= $this->suffix;
            }
        }

        return $value;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'limit' => $this->limit,
            'uppercase' => $this->uppercase,
            'suffix' => $this->suffix,
        ]);
    }
}

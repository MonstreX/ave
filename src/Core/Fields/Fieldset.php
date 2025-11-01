<?php

namespace Monstrex\Ave\Core\Fields;

class Fieldset extends AbstractField
{
    public const TYPE = 'fieldset';

    protected array $schema = [];
    protected ?int $minRows = null;
    protected ?int $maxRows = null;
    protected bool $collapsible = false;

    public function schema(array $fields): static
    {
        $this->schema = $fields;
        return $this;
    }

    public function minRows(int $min): static
    {
        $this->minRows = $min;
        return $this;
    }

    public function maxRows(int $max): static
    {
        $this->maxRows = $max;
        return $this;
    }

    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;
        return $this;
    }

    /**
     * Get fields in this fieldset
     */
    public function getFields(): array
    {
        return $this->schema;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'schema'      => array_map(fn($f) => is_object($f) && method_exists($f, 'toArray') ? $f->toArray() : $f, $this->schema),
            'minRows'     => $this->minRows,
            'maxRows'     => $this->maxRows,
            'collapsible' => $this->collapsible,
        ]);
    }

    public function extract(mixed $raw): mixed
    {
        // Fieldset data is typically an array or JSON string
        if (is_string($raw)) {
            return json_decode($raw, true);
        }
        return $raw;
    }
}

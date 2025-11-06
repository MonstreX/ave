<?php

namespace Monstrex\Ave\Core\Fields;

class Select extends AbstractField
{
    protected array $options = [];
    protected bool $multiple = false;
    protected bool $searchable = true;

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

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options'    => $this->options,
            'multiple'   => $this->multiple,
            'searchable' => $this->searchable,
        ]);
    }
}

<?php

namespace Monstrex\Ave\Core\Fields;

class Number extends AbstractField
{
    protected ?float $min = null;
    protected ?float $max = null;
    protected ?float $step = null;

    public function min(float $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(float $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function step(float $step): static
    {
        $this->step = $step;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'min'  => $this->min,
            'max'  => $this->max,
            'step' => $this->step,
        ]);
    }

    public function extract(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        return (float) $raw;
    }
}

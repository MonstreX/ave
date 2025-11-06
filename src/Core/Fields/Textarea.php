<?php

namespace Monstrex\Ave\Core\Fields;

class Textarea extends AbstractField
{
    protected ?int $rows = null;
    protected ?int $maxLength = null;

    public function rows(int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    public function maxLength(int $length): static
    {
        $this->maxLength = $length;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'rows'      => $this->rows,
            'maxLength' => $this->maxLength,
        ]);
    }
}

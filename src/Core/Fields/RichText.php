<?php

namespace Monstrex\Ave\Core\Fields;

class RichText extends AbstractField
{
    public const TYPE = 'richtext';

    protected array $toolbar = ['bold', 'italic', 'underline', 'link', 'code'];
    protected bool $fullHeight = false;
    protected ?int $minHeight = null;

    public function toolbar(array $tools): static
    {
        $this->toolbar = $tools;
        return $this;
    }

    public function fullHeight(bool $full = true): static
    {
        $this->fullHeight = $full;
        return $this;
    }

    public function minHeight(int $height): static
    {
        $this->minHeight = $height;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'toolbar'    => $this->toolbar,
            'fullHeight' => $this->fullHeight,
            'minHeight'  => $this->minHeight,
        ]);
    }
}

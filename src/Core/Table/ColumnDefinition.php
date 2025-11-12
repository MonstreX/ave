<?php

namespace Monstrex\Ave\Core\Table;

class ColumnDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public bool $sortable,
        public bool $searchable,
        public bool $hidden,
        public string $align,
        public int|string|null $width,
        public ?string $minWidth,
        public ?string $maxWidth,
        public bool $wrap,
        public array $meta,
        public ?string $helpText,
        public ?string $tooltip,
        public ?array $inline,
        public ?array $link = null,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'hidden' => $this->hidden,
            'align' => $this->align,
            'width' => $this->width,
            'minWidth' => $this->minWidth,
            'maxWidth' => $this->maxWidth,
            'wrap' => $this->wrap,
            'meta' => $this->meta,
            'helpText' => $this->helpText,
            'tooltip' => $this->tooltip,
            'inline' => $this->inline,
            'link' => $this->link,
        ];
    }
}


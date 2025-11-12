<?php

namespace Monstrex\Ave\Core\Columns;

use Closure;

class Column
{
    protected string $key;
    protected ?string $label = null;
    protected bool $sortable = false;
    protected bool $searchable = false;
    protected bool $hidden = false;
    protected ?Closure $formatCallback = null;
    protected ?string $align = null;
    /** @var int|string|null */
    protected int|string|null $width = null;
    protected ?string $minWidth = null;
    protected ?string $maxWidth = null;
    protected ?string $cellClass = null;
    protected ?string $headerClass = null;
    protected ?string $helpText = null;
    protected ?string $tooltip = null;
    protected bool $wrap = false;
    protected bool $escape = true;
    protected string $type = 'text';
    protected ?string $view = null;
    protected array $meta = [];
    protected ?array $inline = null;
    protected array|string|null $inlineRules = null;
    protected ?array $linkAction = null;
    protected mixed $linkUrl = null;

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

    public function sortable(bool $on = true): static
    {
        $this->sortable = $on;
        return $this;
    }

    public function searchable(bool $on = true): static
    {
        $this->searchable = $on;
        return $this;
    }

    public function hidden(bool $on = true): static
    {
        $this->hidden = $on;
        return $this;
    }

    public function align(string $align): static
    {
        $this->align = $align;
        return $this;
    }

    public function width(int|string $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function minWidth(string $width): static
    {
        $this->minWidth = $width;
        return $this;
    }

    public function maxWidth(string $width): static
    {
        $this->maxWidth = $width;
        return $this;
    }

    public function wrap(bool $on = true): static
    {
        $this->wrap = $on;
        return $this;
    }

    public function helpText(?string $text): static
    {
        $this->helpText = $text;
        return $this;
    }

    public function cellClass(string $class): static
    {
        $this->cellClass = $class;
        return $this;
    }

    public function headerClass(string $class): static
    {
        $this->headerClass = $class;
        return $this;
    }

    public function tooltip(?string $text): static
    {
        $this->tooltip = $text;
        return $this;
    }

    public function html(bool $enabled = true): static
    {
        $this->escape = !$enabled;
        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function view(string $view): static
    {
        $this->view = $view;
        return $this;
    }

    public function meta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    public function format(Closure $callback): static
    {
        $this->formatCallback = $callback;
        return $this;
    }

    public function linkAction(string $action, array $params = []): static
    {
        $this->linkAction = [
            'action' => $action,
            'params' => $params,
        ];

        return $this;
    }

    /**
     * @param string|Closure $url
     */
    public function linkUrl(string|Closure $url): static
    {
        $this->linkUrl = $url;
        return $this;
    }

    public function inline(string $mode, array $options = []): static
    {
        $this->inline = array_merge([
            'mode' => $mode,
            'field' => $this->key,
        ], $options);

        return $this;
    }

    public function inlineRules(array|string $rules): static
    {
        $this->inlineRules = $rules;
        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function getLabel(): string
    {
        return $this->label ?? ucfirst(str_replace('_', ' ', $this->key));
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function supportsInline(): bool
    {
        return $this->inline !== null;
    }

    public function inlineField(): string
    {
        return $this->inline['field'] ?? $this->key;
    }

    public function inlineMode(): ?string
    {
        return $this->inline['mode'] ?? null;
    }

    public function inlineMeta(): array
    {
        return $this->inline ?? [];
    }

    public function inlineValidationRules(): array|string|null
    {
        return $this->inlineRules;
    }

    public function hasLink(): bool
    {
        return $this->linkAction !== null || $this->linkUrl !== null;
    }

    public function resolveLink(mixed $record, string $resourceClass): ?string
    {
        if ($this->linkUrl instanceof Closure) {
            return call_user_func($this->linkUrl, $record, $resourceClass);
        }

        if (is_string($this->linkUrl)) {
            return $this->linkUrl;
        }

        if ($this->linkAction) {
            $action = $this->linkAction['action'];
            $params = $this->linkAction['params'];
            $slug = $params['slug'] ?? $resourceClass::getSlug();

            return match ($action) {
                'edit' => route('ave.resource.edit', ['slug' => $slug, 'id' => $record->getKey()]),
                'view', 'show' => route('ave.resource.edit', ['slug' => $slug, 'id' => $record->getKey()]),
                default => route("ave.resource.$action", array_merge(['slug' => $slug, 'id' => $record->getKey()], $params)),
            };
        }

        return null;
    }

    public function shouldEscape(): bool
    {
        return $this->escape;
    }

    public function shouldWrap(): bool
    {
        return $this->wrap;
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    public function getCellClass(): string
    {
        return $this->cellClass ?? '';
    }

    public function getHeaderClass(): string
    {
        return $this->headerClass ?? '';
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    public function getWidth(): int|string|null
    {
        return $this->width;
    }

    public function getMinWidth(): ?string
    {
        return $this->minWidth;
    }

    public function getMaxWidth(): ?string
    {
        return $this->maxWidth;
    }

    public function getAlign(): string
    {
        return $this->align ?? 'left';
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function resolveRecordValue(mixed $record): mixed
    {
        return data_get($record, $this->key);
    }

    public function resolveView(): string
    {
        return $this->view ?? static::defaultViewFor($this->type);
    }

    public function resolveTemplateData(mixed $record): array
    {
        return [];
    }

    protected static function defaultViewFor(string $type): string
    {
        return match ($type) {
            'boolean' => 'ave::components.tables.boolean-column',
            'badge' => 'ave::components.tables.badge-column',
            'image' => 'ave::components.tables.image-column',
            'template' => 'ave::components.tables.template-column',
            'date' => 'ave::components.tables.date-column',
            default => 'ave::components.tables.text-column',
        };
    }

    public function formatValue(mixed $value, mixed $record): mixed
    {
        if ($this->formatCallback) {
            return call_user_func($this->formatCallback, $value, $record);
        }

        return $value;
    }

    public function toArray(): array
    {
        return [
            'key'        => $this->key,
            'label'      => $this->getLabel(),
            'sortable'   => $this->sortable,
            'searchable' => $this->searchable,
            'hidden'     => $this->hidden,
            'align'      => $this->getAlign(),
            'width'      => $this->width,
            'minWidth'   => $this->minWidth,
            'maxWidth'   => $this->maxWidth,
            'wrap'       => $this->wrap,
            'view'       => $this->resolveView(),
            'type'       => $this->type,
            'meta'       => $this->meta,
            'helpText'   => $this->helpText,
            'tooltip'    => $this->tooltip,
            'inline'     => $this->inline,
            'linkAction' => $this->linkAction,
        ];
    }
}

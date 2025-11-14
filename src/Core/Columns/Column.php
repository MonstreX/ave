<?php

namespace Monstrex\Ave\Core\Columns;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Monstrex\Ave\Core\Table\ColumnDefinition;
use Monstrex\Ave\Core\Table\ColumnViewRegistry;

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
    // Styling properties
    protected ?string $fontSize = null;
    protected int|string|null $fontWeight = null;
    protected bool $fontItalic = false;
    protected bool $fontBold = false;
    protected ?string $textColor = null;

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

    /**
     * Set font size for the column value.
     * 
     * @param string $size CSS font-size value (e.g., '12px', '0.875rem', '14px')
     */
    public function fontSize(string $size): static
    {
        $this->fontSize = $size;
        return $this;
    }

    /**
     * Set font weight for the column value.
     * 
     * @param int|string $weight CSS font-weight value (e.g., 400, 600, 700, 'normal', 'bold')
     */
    public function fontWeight(int|string $weight): static
    {
        $this->fontWeight = (string) $weight;
        return $this;
    }

    /**
     * Make the column value bold (font-weight: 700).
     */
    public function bold(bool $on = true): static
    {
        $this->fontBold = $on;
        if ($on) {
            $this->fontWeight = '700';
        } elseif ($this->fontWeight === '700') {
            $this->fontWeight = null;
        }
        return $this;
    }

    /**
     * Make the column value italic (font-style: italic).
     */
    public function italic(bool $on = true): static
    {
        $this->fontItalic = $on;
        return $this;
    }

    /**
     * Set text color for the column value.
     * 
     * @param string $color CSS color value (e.g., '#ff0000', 'red', 'rgb(255, 0, 0)')
     */
    public function color(string $color): static
    {
        $this->textColor = $color;
        return $this;
    }

    public function linkAction(string $action, array $params = [], ?string $ability = null): static
    {
        $this->linkAction = [
            'action' => $action,
            'params' => $params,
            'ability' => $ability ?? $this->guessAbilityForAction($action),
        ];

        return $this;
    }

    public function linkToEdit(array $params = []): static
    {
        return $this->linkAction('edit', $params, 'update');
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

    public function resolveLink(mixed $record, string $resourceClass, ?Authenticatable $user = null): ?string
    {
        if (!$this->hasLink()) {
            return null;
        }

        if ($this->linkUrl instanceof Closure) {
            return call_user_func($this->linkUrl, $record, $resourceClass);
        }

        if (is_string($this->linkUrl)) {
            return $this->linkUrl;
        }

        $resourceInstance = new $resourceClass();

        if ($this->linkAction) {
            $ability = $this->linkAction['ability'] ?? 'view';
            if ($user && method_exists($resourceInstance, 'can')) {
                if (!$resourceInstance->can($ability, $user, $record)) {
                    return null;
                }
            }

            $params = $this->linkAction['params'] ?? [];
            $slug = $params['slug'] ?? $resourceClass::getSlug();
            unset($params['slug']);

            $routeName = $params['route'] ?? $this->defaultRouteForAction($this->linkAction['action']);
            unset($params['route']);

            if (str_starts_with($routeName, 'ave.resource.') && !array_key_exists('slug', $params)) {
                $params['slug'] = $slug;
            }

            if (!array_key_exists('id', $params) && in_array($routeName, ['ave.resource.edit', 'ave.resource.update', 'ave.resource.destroy'])) {
                $params['id'] = $record->getKey();
            }

            return route($routeName, $params);
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

    /**
     * Get inline styles string for the column cell.
     */
    public function getCellStyle(): string
    {
        $styles = [];

        if ($this->fontSize !== null) {
            $styles[] = "font-size: {$this->fontSize}";
        }

        if ($this->fontWeight !== null) {
            $styles[] = "font-weight: {$this->fontWeight}";
        }

        if ($this->fontItalic) {
            $styles[] = "font-style: italic";
        }

        if ($this->textColor !== null) {
            $styles[] = "color: {$this->textColor}";
        }

        return implode('; ', $styles);
    }

    /**
     * Check if column has any custom styles.
     */
    public function hasCustomStyles(): bool
    {
        return $this->fontSize !== null
            || $this->fontWeight !== null
            || $this->fontItalic
            || $this->textColor !== null;
    }

    public function resolveRecordValue(mixed $record): mixed
    {
        return data_get($record, $this->key);
    }

    public function resolveView(): string
    {
        if ($this->view) {
            return $this->view;
        }

        return ColumnViewRegistry::resolve($this->type);
    }

    public function resolveTemplateData(mixed $record): array
    {
        return [];
    }

    protected function defaultRouteForAction(string $action): string
    {
        return match ($action) {
            'edit', 'update' => 'ave.resource.edit',
            'create' => 'ave.resource.create',
            'index' => 'ave.resource.index',
            default => $action,
        };
    }

    protected function guessAbilityForAction(string $action): string
    {
        return match ($action) {
            'edit', 'update' => 'update',
            'create' => 'create',
            'destroy', 'delete' => 'delete',
            default => 'view',
        };
    }

    public function formatValue(mixed $value, mixed $record): mixed
    {
        if ($this->formatCallback) {
            return call_user_func($this->formatCallback, $value, $record);
        }

        return $value;
    }

    public function toDefinition(): ColumnDefinition
    {
        $linkMeta = null;
        if ($this->linkAction) {
            $linkMeta = [
                'type' => 'action',
                'action' => $this->linkAction['action'],
                'ability' => $this->linkAction['ability'] ?? null,
            ];
        } elseif (is_string($this->linkUrl)) {
            $linkMeta = [
                'type' => 'url',
                'url' => $this->linkUrl,
            ];
        }

        return new ColumnDefinition(
            key: $this->key,
            label: $this->getLabel(),
            type: $this->type,
            sortable: $this->sortable,
            searchable: $this->searchable,
            hidden: $this->hidden,
            align: $this->getAlign(),
            width: $this->width,
            minWidth: $this->minWidth,
            maxWidth: $this->maxWidth,
            wrap: $this->wrap,
            meta: $this->meta,
            helpText: $this->helpText,
            tooltip: $this->tooltip,
            inline: $this->inline,
            link: $linkMeta,
        );
    }

    public function toArray(): array
    {
        return $this->toDefinition()->toArray();
    }
}

<?php

namespace Monstrex\Ave\Core\Columns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ImageColumn extends Column
{
    protected string $type = 'image';
    protected bool $multiple = false;
    protected int $size = 48;
    protected string $shape = 'square';
    protected bool $lightbox = false;
    protected ?string $fallback = null;
    protected string $display = 'stack';
    protected string $fit = 'height';
    protected ?int $customHeight = null;
    protected string $source = 'attribute';
    protected ?string $mediaCollection = null;
    protected ?string $mediaConversion = null;

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function multiple(bool $on = true): static
    {
        $this->multiple = $on;
        return $this;
    }

    public function size(int $pixels): static
    {
        $this->size = $pixels;
        return $this;
    }

    public function shape(string $shape): static
    {
        $this->shape = $shape;
        return $this;
    }

    public function lightbox(bool $on = true): static
    {
        $this->lightbox = $on;
        return $this;
    }

    public function fallback(?string $url): static
    {
        $this->fallback = $url;
        return $this;
    }

    public function display(string $mode): static
    {
        $this->display = $mode;
        return $this;
    }

    public function height(int $pixels): static
    {
        $this->customHeight = max(0, $pixels);
        return $this;
    }

    public function cover(bool $on = true): static
    {
        $this->fit = $on ? 'cover' : 'height';
        return $this;
    }

    public function fit(string $mode): static
    {
        $allowed = ['cover', 'height'];
        $this->fit = in_array($mode, $allowed, true) ? $mode : 'height';
        return $this;
    }

    public function fromMedia(?string $collection = null, ?string $conversion = null): static
    {
        $this->source = 'media';
        $this->mediaCollection = $collection;
        $this->mediaConversion = $conversion;
        return $this;
    }

    public function mediaCollection(string $collection): static
    {
        $this->mediaCollection = $collection;
        return $this;
    }

    public function mediaConversion(?string $conversion): static
    {
        $this->mediaConversion = $conversion;
        return $this;
    }

    public function formatValue(mixed $value, mixed $record): mixed
    {
        $value = parent::formatValue($value, $record);

        if ($this->source === 'media') {
            $images = $this->extractFromMedia($record);

            if ($this->multiple) {
                return !empty($images) ? $images : ($this->fallback ? [$this->fallback] : []);
            }

            return $images[0] ?? $this->fallback;
        }

        if ($this->multiple) {
            return $this->normalizeList($value);
        }

        $list = $this->normalizeList($value);

        if (empty($list) && $this->fallback) {
            $list[] = $this->fallback;
        }

        return $list[0] ?? null;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'multiple' => $this->multiple,
            'size' => $this->size,
            'shape' => $this->shape,
            'lightbox' => $this->lightbox,
        ]);
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getShape(): string
    {
        return $this->shape;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function usesLightbox(): bool
    {
        return $this->lightbox;
    }

    public function getFallback(): ?string
    {
        return $this->fallback;
    }

    public function getDisplay(): string
    {
        return $this->display;
    }

    public function getFit(): string
    {
        return $this->fit;
    }

    public function getHeight(): ?int
    {
        return $this->customHeight;
    }

    protected function extractFromMedia(mixed $record): array
    {
        if (!$record) {
            return [];
        }

        $collection = $this->mediaCollection ?? $this->key();

        if ($this->multiple) {
            if (!method_exists($record, 'getMedia')) {
                return [];
            }

            $mediaItems = $record->getMedia($collection);

            return collect($mediaItems)
                ->map(fn ($media) => $this->mediaItemUrl($media))
                ->filter()
                ->values()
                ->all();
        }

        if (!method_exists($record, 'getFirstMedia')) {
            return [];
        }

        $media = $record->getFirstMedia($collection);
        $url = $this->mediaItemUrl($media);

        return $url ? [$url] : [];
    }

    protected function mediaItemUrl($media): ?string
    {
        if (!$media) {
            return null;
        }

        $conversion = $this->mediaConversion;

        if ($conversion) {
            [$width, $height, $format] = $this->parseConversion($conversion);

            if ($width || $height) {
                $media = $media->crop($width, $height);
            }

            if ($format) {
                $media = $media->format($format);
            }
        }

        return $media->url();
    }

    protected function parseConversion(string $conversion): array
    {
        $format = '';

        if (str_contains($conversion, '.')) {
            [$dimensions, $format] = explode('.', $conversion);
        } else {
            $dimensions = $conversion;
        }

        $parts = explode('x', $dimensions);
        $width = (int) ($parts[0] ?? 0);
        $height = (int) ($parts[1] ?? 0);

        return [$width, $height, $format];
    }

    protected function normalizeList(mixed $value): array
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        $items = Arr::wrap($value);

        if (empty($items) && $this->fallback) {
            $items[] = $this->fallback;
        }

        return collect($items)
            ->map(fn ($item) => $this->resolveUrl($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function resolveUrl(mixed $item): ?string
    {
        if (is_array($item)) {
            $item = $item['url'] ?? $item['path'] ?? null;
        } elseif (is_object($item)) {
            if (method_exists($item, 'url')) {
                $item = $item->url();
            } elseif (isset($item->url)) {
                $item = $item->url;
            } elseif (isset($item->path)) {
                $item = $item->path;
            }
        }

        if (!$item || !is_string($item)) {
            return null;
        }

        $item = trim($item);

        if ($item === '') {
            return null;
        }

        if (Str::startsWith($item, ['http://', 'https://', '//', 'data:'])) {
            return $item;
        }

        if (Str::startsWith($item, ['/'])) {
            return $item;
        }

        return asset($item);
    }
}

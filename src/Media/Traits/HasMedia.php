<?php

namespace Monstrex\Ave\Media\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Monstrex\Ave\Models\Media;

trait HasMedia
{
    /**
     * Get all media for the model
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    /**
     * Get media from specific collection
     */
    public function getMedia(string $collection = 'default')
    {
        return $this->media()
            ->where('collection_name', $collection)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get first media item from collection
     */
    public function getFirstMedia(string $collection = 'default'): ?Media
    {
        return $this->media()
            ->where('collection_name', $collection)
            ->orderBy('order')
            ->first();
    }

    /**
     * Get media URL from collection
     */
    public function getFirstMediaUrl(string $collection = 'default', string $conversion = ''): string
    {
        $media = $this->getFirstMedia($collection);

        if (! $media) {
            return '';
        }

        if ($conversion) {
            [$width, $height, $format] = $this->parseConversion($conversion);

            return $media->crop($width, $height)->format($format)->url();
        }

        return $media->url();
    }

    /**
     * Parse conversion string like '300x200' or '300x200.webp'
     */
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

    /**
     * Delete all media when model is deleted
     */
    public static function bootHasMedia(): void
    {
        static::deleting(function ($model) {
            $model->media()->each(function ($media) {
                $media->delete();
            });
        });
    }
}

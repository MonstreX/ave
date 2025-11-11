<?php

namespace Monstrex\Ave\Support;

use Illuminate\Support\Arr;
use Monstrex\Ave\Services\FilenameGeneratorService;
use Monstrex\Ave\Services\PathGeneratorService;

class StorageProfile
{
    protected array $config;

    /**
     * @var array<string,mixed>
     */
    protected array $overrides = [];

    public function __construct(array $config, array $overrides = [])
    {
        $this->config = $config;
        $this->overrides = $overrides;
    }

    public static function make(array $overrides = []): self
    {
        return new self(config('ave.storage', []), $overrides);
    }

    public function with(array $overrides): self
    {
        $clone = clone $this;
        $clone->overrides = array_merge($this->overrides, $overrides);

        return $clone;
    }

    public function disk(): string
    {
        return (string) $this->value('disk', 'public');
    }

    public function baseRoot(): string
    {
        return trim((string) $this->value('root', 'files'), '/');
    }

    public function pathPrefix(): ?string
    {
        $prefix = $this->value('path_prefix');

        if ($prefix === null || $prefix === '') {
            return null;
        }

        return trim((string) $prefix, '/');
    }

    public function resolvedRoot(?string $overridePrefix = null): string
    {
        $root = $this->baseRoot();
        $prefix = $overridePrefix ?? $this->pathPrefix();

        if ($prefix) {
            return $root . '/' . $prefix;
        }

        return $root;
    }

    public function pathStrategy(): string
    {
        return (string) $this->value('path.strategy', PathGeneratorService::STRATEGY_DATED);
    }

    public function filenameStrategy(): string
    {
        return (string) $this->value('filename.strategy', FilenameGeneratorService::STRATEGY_TRANSLITERATE);
    }

    public function filenameSeparator(): string
    {
        return (string) $this->value('filename.separator', '-');
    }

    public function filenameLocale(): string
    {
        return (string) $this->value('filename.locale', 'en');
    }

    public function filenameUniqueness(): string
    {
        return (string) $this->value('filename.uniqueness', FilenameGeneratorService::UNIQUENESS_SUFFIX);
    }

    public function imageMaxSize(): ?int
    {
        $value = $this->value('image.max_size');

        return $value !== null ? (int) $value : null;
    }

    public function buildPath(array $options = []): string
    {
        if (!empty($options['customPath'])) {
            return trim((string) $options['customPath'], '/');
        }

        return PathGeneratorService::generate([
            'root' => $this->resolvedRoot($options['pathPrefix'] ?? null),
            'strategy' => $options['pathStrategy'] ?? $this->pathStrategy(),
            'model' => $options['model'] ?? null,
            'recordId' => $options['recordId'] ?? null,
            'year' => $options['year'] ?? null,
            'month' => $options['month'] ?? null,
        ]);
    }

    public function filenameOptions(array $options = []): array
    {
        $replace = (bool) ($options['replaceFile'] ?? false);

        return [
            'strategy' => $options['filenameStrategy'] ?? $this->filenameStrategy(),
            'separator' => $options['filenameSeparator'] ?? $this->filenameSeparator(),
            'locale' => $options['filenameLocale'] ?? $this->filenameLocale(),
            'uniqueness' => $replace
                ? FilenameGeneratorService::UNIQUENESS_REPLACE
                : $options['filenameUniqueness'] ?? $this->filenameUniqueness(),
            'existsCallback' => $options['existsCallback'] ?? null,
        ];
    }

    public function generateFilename(string $originalName, array $options = []): string
    {
        return FilenameGeneratorService::generate($originalName, $this->filenameOptions($options));
    }

    protected function value(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->overrides)) {
            return $this->overrides[$key];
        }

        return Arr::get($this->config, $key, $default);
    }
}

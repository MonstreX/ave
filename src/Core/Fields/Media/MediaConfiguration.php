<?php

namespace Monstrex\Ave\Core\Fields\Media;

/**
 * Immutable-style configuration holder for the Media field.
 *
 * Provides a single place to manage field options while keeping the Media
 * field class focused on behaviour.
 */
class MediaConfiguration
{
    protected ?string $collection = null;

    protected ?string $collectionOverride = null;

    protected bool $multiple = false;

    protected ?int $maxFiles = null;

    protected array $accept = [];

    protected ?int $maxFileSize = null;

    protected bool $showPreview = true;

    protected array $imageConversions = [];

    protected int $columns = 6;

    protected array $propNames = [];

    protected ?int $maxImageSize = null;

    protected string $pathStrategy = '';

    protected ?\Closure $pathGenerator = null;

    public function __clone()
    {
        $this->accept = array_values($this->accept);
        $this->imageConversions = array_map(
            static fn (array $conversion): array => $conversion,
            $this->imageConversions
        );
        $this->propNames = array_values($this->propNames);
    }

    public function setCollection(string $collection): void
    {
        $this->collection = $collection;
    }

    public function collection(): ?string
    {
        return $this->collection;
    }

    public function setCollectionOverride(?string $override): void
    {
        $this->collectionOverride = $override;
    }

    public function collectionOverride(): ?string
    {
        return $this->collectionOverride;
    }

    public function setMultiple(bool $multiple, ?int $maxFiles = null): void
    {
        $this->multiple = $multiple;
        $this->maxFiles = $maxFiles;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setAccept(array $mimeTypes): void
    {
        $this->accept = $mimeTypes;
    }

    public function accept(): array
    {
        return $this->accept;
    }

    public function setMaxFileSize(?int $sizeInKb): void
    {
        $this->maxFileSize = $sizeInKb;
    }

    public function maxFileSize(): ?int
    {
        return $this->maxFileSize;
    }

    public function setMaxFiles(?int $maxFiles): void
    {
        $this->maxFiles = $maxFiles;
    }

    public function maxFiles(): ?int
    {
        return $this->maxFiles;
    }

    public function setShowPreview(bool $show): void
    {
        $this->showPreview = $show;
    }

    public function showPreview(): bool
    {
        return $this->showPreview;
    }

    public function setImageConversions(array $conversions): void
    {
        $this->imageConversions = $conversions;
    }

    public function imageConversions(): array
    {
        return $this->imageConversions;
    }

    public function setColumns(int $columns): void
    {
        $this->columns = $columns;
    }

    public function columns(): int
    {
        return $this->columns;
    }

    public function setPropNames(array $names): void
    {
        $this->propNames = $names;
    }

    public function propNames(): array
    {
        return $this->propNames;
    }

    public function setMaxImageSize(?int $sizeInPx): void
    {
        $this->maxImageSize = $sizeInPx;
    }

    public function maxImageSize(): ?int
    {
        return $this->maxImageSize;
    }

    public function setPathStrategy(string $strategy): void
    {
        $this->pathStrategy = $strategy;
    }

    public function pathStrategy(): string
    {
        return $this->pathStrategy;
    }

    public function setPathGenerator(?\Closure $generator): void
    {
        $this->pathGenerator = $generator;
    }

    public function pathGenerator(): ?\Closure
    {
        return $this->pathGenerator;
    }
}


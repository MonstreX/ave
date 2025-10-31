<?php

namespace Monstrex\Ave\Core\Fields;

class FileUpload extends AbstractField
{
    public const TYPE = 'file';

    protected bool $multiple = false;
    protected array $acceptedMimes = [];
    protected ?int $maxSize = null; // in bytes
    protected ?string $disk = 'public';
    protected ?string $path = null;

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function acceptedMimes(array $mimes): static
    {
        $this->acceptedMimes = $mimes;
        return $this;
    }

    public function maxSize(int $bytes): static
    {
        $this->maxSize = $bytes;
        return $this;
    }

    public function disk(string $disk): static
    {
        $this->disk = $disk;
        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'multiple'       => $this->multiple,
            'acceptedMimes'  => $this->acceptedMimes,
            'maxSize'        => $this->maxSize,
            'disk'           => $this->disk,
            'path'           => $this->path,
        ]);
    }
}

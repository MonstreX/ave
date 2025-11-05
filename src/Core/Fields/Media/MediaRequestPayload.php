<?php

namespace Monstrex\Ave\Core\Fields\Media;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Value object describing incoming media payload from the request.
 */
class MediaRequestPayload
{
    /**
     * @param array<int,int> $uploaded
     * @param array<int,int> $deleted
     * @param array<int,int> $order
     * @param array<int,array<string,mixed>> $props
     */
    public function __construct(
        protected array $uploaded,
        protected array $deleted,
        protected array $order,
        protected array $props,
        protected string $metaKey,
    ) {
    }

    public static function capture(string $metaKey, Request $request): self
    {
        $uploaded = self::parseIdList(Arr::get($request->input('__media_uploaded', []), $metaKey));
        $deleted = self::parseIdList(Arr::get($request->input('__media_deleted', []), $metaKey));
        $order = self::parseIdList(Arr::get($request->input('__media_order', []), $metaKey));
        $rawProps = Arr::get($request->input('__media_props', []), $metaKey, []);

        $props = [];
        if (is_array($rawProps)) {
            foreach ($rawProps as $id => $value) {
                $mediaId = (int) $id;
                if ($mediaId <= 0) {
                    continue;
                }

                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? $decoded : [];
                }

                if (!is_array($value)) {
                    continue;
                }

                $props[$mediaId] = $value;
            }
        }

        return new self($uploaded, $deleted, $order, $props, $metaKey);
    }

    /**
     * @return array<int,int>
     */
    public function uploaded(): array
    {
        return $this->uploaded;
    }

    /**
     * @return array<int,int>
     */
    public function deleted(): array
    {
        return $this->deleted;
    }

    /**
     * @return array<int,int>
     */
    public function order(): array
    {
        return $this->order;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function props(): array
    {
        return $this->props;
    }

    public function metaKey(): string
    {
        return $this->metaKey;
    }

    public function hasChanges(): bool
    {
        return !empty($this->uploaded) || !empty($this->order) || !empty($this->props);
    }

    /**
     * @param mixed $value
     * @return array<int,int>
     */
    protected static function parseIdList(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $entry) {
            if ($entry === null || $entry === '') {
                continue;
            }

            $ids[] = (int) $entry;
        }

        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }
}


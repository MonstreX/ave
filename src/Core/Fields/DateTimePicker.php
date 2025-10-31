<?php

namespace Monstrex\Ave\Core\Fields;

class DateTimePicker extends AbstractField
{
    public const TYPE = 'datetime';

    protected string $format = 'Y-m-d H:i:s';
    protected bool $withTime = true;
    protected ?string $minDate = null;
    protected ?string $maxDate = null;

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function withTime(bool $withTime = true): static
    {
        $this->withTime = $withTime;
        if (!$withTime) {
            $this->format = 'Y-m-d';
        }
        return $this;
    }

    public function minDate(string $date): static
    {
        $this->minDate = $date;
        return $this;
    }

    public function maxDate(string $date): static
    {
        $this->maxDate = $date;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'format'   => $this->format,
            'withTime' => $this->withTime,
            'minDate'  => $this->minDate,
            'maxDate'  => $this->maxDate,
        ]);
    }

    public function extract(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        return $raw;
    }
}

<?php

namespace Monstrex\Ave\Core\Fields;

class Toggle extends AbstractField
{
    public const TYPE = 'toggle';

    public function toArray(): array
    {
        return parent::toArray();
    }

    public function extract(mixed $raw): mixed
    {
        // Convert checkbox value to boolean
        if ($raw === 'on' || $raw === '1' || $raw === 1 || $raw === true) {
            return true;
        }
        return false;
    }
}

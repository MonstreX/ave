<?php

namespace Monstrex\Ave\Core\Fields;

class Hidden extends AbstractField
{
    public const TYPE = 'hidden';

    public function toArray(): array
    {
        return parent::toArray();
    }
}

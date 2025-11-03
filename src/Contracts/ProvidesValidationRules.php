<?php

namespace Monstrex\Ave\Contracts;

interface ProvidesValidationRules
{
    /**
     * @return array<string,string>
     */
    public function buildValidationRules(): array;
}

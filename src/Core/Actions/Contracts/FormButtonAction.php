<?php

namespace Monstrex\Ave\Core\Actions\Contracts;

use Monstrex\Ave\Core\Form;

interface FormButtonAction extends FormAction
{
    public function buttonType(): string; // submit|link

    public function intent(): ?string;

    public function labelForMode(string $mode): string;

    public function resolveUrl(string $slug, Form $form, mixed $model, bool $isEdit): ?string;
}


<?php

namespace Monstrex\Ave\Core\Actions\Form;

use Monstrex\Ave\Core\Form;

class CancelFormAction extends AbstractFormButtonAction
{
    protected string $key = 'cancel';
    protected string $color = 'secondary';
    protected string $buttonType = 'link';

    public function label(): string
    {
        return __('Cancel');
    }

    public function intent(): ?string
    {
        return null;
    }

    public function resolveUrl(string $slug, Form $form, mixed $model, bool $isEdit): ?string
    {
        return $form->getCancelUrl() ?? route('ave.resource.index', ['slug' => $slug]);
    }
}


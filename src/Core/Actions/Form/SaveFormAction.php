<?php

namespace Monstrex\Ave\Core\Actions\Form;

class SaveFormAction extends AbstractFormButtonAction
{
    protected string $key = 'save';
    protected string $color = 'primary';
    protected ?string $intent = 'save';

    public function label(): string
    {
        return __('Save');
    }

    public function labelForMode(string $mode): string
    {
        return $mode === 'edit' ? __('Update') : __('Save');
    }
}


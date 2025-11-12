<?php

namespace Monstrex\Ave\Core\Actions\Form;

class SaveAndContinueFormAction extends AbstractFormButtonAction
{
    protected string $key = 'save-continue';
    protected string $color = 'success';
    protected ?string $intent = 'save-continue';

    public function label(): string
    {
        return __('Save & Continue');
    }

    public function labelForMode(string $mode): string
    {
        return $mode === 'edit'
            ? __('Update & Continue')
            : __('Save & Continue');
    }
}


<?php

namespace Monstrex\Ave\Core\Actions\Form;

class SaveAndContinueFormAction extends AbstractFormButtonAction
{
    protected string $key = 'save-continue';
    protected string $color = 'success';
    protected ?string $intent = 'save-continue';

    public function label(): string
    {
        return __('ave::actions.save_and_continue');
    }

    public function labelForMode(string $mode): string
    {
        return $mode === 'edit'
            ? __('ave::actions.update_and_continue')
            : __('ave::actions.save_and_continue');
    }
}


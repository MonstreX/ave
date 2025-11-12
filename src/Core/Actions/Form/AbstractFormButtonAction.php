<?php

namespace Monstrex\Ave\Core\Actions\Form;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\BaseAction;
use Monstrex\Ave\Core\Actions\Contracts\FormButtonAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Core\Form;

abstract class AbstractFormButtonAction extends BaseAction implements FormButtonAction
{
    protected string $buttonType = 'submit'; // or link
    protected ?string $intent = null;

    public function buttonType(): string
    {
        return $this->buttonType;
    }

    public function intent(): ?string
    {
        return $this->intent;
    }

    public function labelForMode(string $mode): string
    {
        return $this->label();
    }

    public function resolveUrl(string $slug, Form $form, mixed $model, bool $isEdit): ?string
    {
        return null;
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        throw new \LogicException(sprintf('Form button action "%s" should not be executed via controller.', $this->key()));
    }
}


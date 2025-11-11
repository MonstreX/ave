<?php

namespace Monstrex\Ave\Core\Actions\Contracts;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Support\ActionContext;

interface ActionInterface
{
    public function key(): string;

    public function label(): string;

    public function icon(): ?string;

    public function color(): string;

    public function confirm(): ?string;

    public function form(): array;

    public function rules(): array;

    public function ability(): ?string;

    public function authorize(ActionContext $context): bool;

    public function handle(ActionContext $context, Request $request): mixed;
}

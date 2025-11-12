<?php

namespace Monstrex\Ave\Core\Columns;

use Closure;

class TemplateColumn extends Column
{
    protected string $type = 'template';
    protected ?string $templateView = null;
    protected ?Closure $dataResolver = null;
    protected array $staticData = [];

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function template(string $view): static
    {
        $this->templateView = $view;
        return $this;
    }

    /**
     * @param array<string,mixed>|Closure $data
     */
    public function data(array|Closure $data): static
    {
        if ($data instanceof Closure) {
            $this->dataResolver = $data;
        } else {
            $this->staticData = $data;
        }

        return $this;
    }

    public function getTemplateView(): ?string
    {
        return $this->templateView;
    }

    public function resolveTemplateData(mixed $record): array
    {
        $resolved = $this->staticData;

        if ($this->dataResolver) {
            $dynamic = call_user_func($this->dataResolver, $record);
            if (is_array($dynamic)) {
                $resolved = array_merge($resolved, $dynamic);
            }
        }

        return $resolved;
    }
}


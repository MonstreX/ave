<?php

namespace Monstrex\Ave\Core\Columns;

use Closure;

class TemplateColumn extends Column
{
    protected string $type = 'template';
    protected ?string $templateView = null;
    protected ?Closure $dataResolver = null;
    protected array $staticData = [];

    /**
     * TemplateColumn is NEVER escaped by default since it renders custom views.
     * The template view itself is responsible for escaping user data.
     *
     * Security: Always use {{ $var }} (escaped) in template views, not {!! $var !!}
     * unless you're absolutely sure the content is safe HTML.
     */
    protected bool $escape = false;

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

    /**
     * Sanitize HTML content for safe display.
     *
     * Use this method in your template views when displaying user-generated content:
     * @example
     * {!! $column->sanitize($user_content) !!}
     *
     * @param string|null $html
     * @param array<string> $allowedTags Tags to allow (default: basic formatting)
     * @return string
     */
    public function sanitize(?string $html, array $allowedTags = ['b', 'i', 'u', 'strong', 'em', 'a', 'p', 'br']): string
    {
        if ($html === null) {
            return '';
        }

        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        return strip_tags($html, $allowedTagsString);
    }
}


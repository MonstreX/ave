<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Http\Request;
use Monstrex\Ave\Contracts\HandlesFormRequest;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Services\SlugService;

/**
 * Slug Field - generates URL-friendly slugs from another field.
 *
 * Provides a form field for managing URL slugs with automatic generation
 * from a source field (typically 'title'). Client requests slug generation
 * via AJAX, ensuring server-side processing with consistent transliteration.
 *
 * Features:
 * - Auto-generation from source field on client focus
 * - Server-side slug generation (single source of truth)
 * - Optional uniqueness validation
 * - Customizable separator and locale
 *
 * Example:
 *   Slug::make('slug')
 *       ->label('URL Slug')
 *       ->from('title')
 *       ->separator('-')
 *       ->locale('ru')
 *       ->unique('articles')
 */
class Slug extends AbstractField implements HandlesFormRequest
{
    protected string $type = 'slug';

    // Configuration
    protected ?string $from = null;
    protected string $separator = '-';
    protected ?string $locale = null;
    protected bool $unique = false;
    protected ?string $uniqueTable = null;
    protected ?string $uniqueColumn = null;

    /**
     * Set the source field for slug generation.
     *
     * @param string $field The field name to generate slug from (e.g., 'title')
     * @return static
     */
    public function from(string $field): static
    {
        $this->from = $field;
        return $this;
    }

    /**
     * Set the slug separator character.
     *
     * @param string $separator Character to use between words (default: '-')
     * @return static
     */
    public function separator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Set the locale for transliteration.
     *
     * @param string|null $locale Language locale code (e.g., 'ru', 'uk')
     * @return static
     */
    public function locale(?string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Enable uniqueness validation.
     *
     * @param string $table Database table name to check uniqueness
     * @param string|null $column Column name (default: field key)
     * @return static
     */
    public function unique(string $table, ?string $column = null): static
    {
        $this->unique = true;
        $this->uniqueTable = $table;
        $this->uniqueColumn = $column;
        return $this;
    }

    /**
     * Get the source field name.
     *
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * Get the separator character.
     *
     * @return string
     */
    public function getSeparator(): string
    {
        return $this->separator;
    }

    /**
     * Get the locale for transliteration.
     *
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Prepare request - normalize slug value if provided.
     *
     * Server-side normalization ensures consistency with client generation.
     * If slug is empty, it will be auto-generated on client side via AJAX.
     *
     * @param Request $request
     * @param FormContext $context
     * @return void
     */
    public function prepareRequest(Request $request, FormContext $context): void
    {
        $slugValue = $request->input($this->key);

        // Normalize only if slug is not empty (auto-generation happens on client)
        if (!empty($slugValue)) {
            $normalized = SlugService::make($slugValue, $this->separator, $this->locale);
            $request->merge([$this->key => $normalized]);
        }
    }

    /**
     * Build validation rules including uniqueness if configured.
     *
     * @return array<string, string>
     */
    public function getRules(): array
    {
        $rules = parent::getRules();

        if ($this->unique && $this->uniqueTable) {
            $column = $this->uniqueColumn ?? $this->key;
            $rules[] = "unique:{$this->uniqueTable},{$column}";
        }

        return $rules;
    }

    /**
     * Convert field to array for Blade rendering.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'from' => $this->from,
            'separator' => $this->separator,
            'locale' => $this->locale,
        ]);
    }
}

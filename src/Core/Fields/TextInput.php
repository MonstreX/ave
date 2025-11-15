<?php

namespace Monstrex\Ave\Core\Fields;

use Monstrex\Ave\Contracts\ProvidesValidationAttributes;

/**
 * TextInput Field
 *
 * A basic text input field for single-line text data.
 *
 * Features:
 * - Minimum and maximum length constraints
 * - Regular expression pattern validation
 * - Multiple input types: text, email, url, tel, number, password
 * - Prefix and suffix support (e.g., $, %, @)
 * - HTML input type: text, email, url, tel, etc.
 *
 * Example:
 *   TextInput::make('username')
 *       ->label('Username')
 *       ->required()
 *       ->minLength(3)
 *       ->maxLength(50)
 *       ->pattern('^[a-zA-Z0-9_]+$')
 *
 * Example (Email variant):
 *   TextInput::make('email')->email()->required()
 *
 * Example (URL variant):
 *   TextInput::make('website')->url()
 *
 * Example (Tel variant):
 *   TextInput::make('phone')->tel()
 *
 * Example (With prefix):
 *   TextInput::make('price')->number()->prefix('$')
 */
class TextInput extends AbstractField implements ProvidesValidationAttributes
{
    /**
     * Maximum allowed length in characters
     */
    protected ?string $maxLength = null;

    /**
     * Minimum required length in characters
     */
    protected ?string $minLength = null;

    /**
     * Regular expression pattern for validation
     */
    protected ?string $pattern = null;

    /**
     * HTML input type (text, email, url, tel, number, password)
     */
    protected string $inputType = 'text';

    /**
     * Prefix text shown before input (e.g., $, %, @)
     */
    protected ?string $prefixText = null;

    /**
     * Suffix text shown after input (e.g., .00, %, km)
     */
    protected ?string $suffixText = null;

    /**
     * Set maximum allowed length
     *
     * @param int $length Maximum number of characters allowed
     * @return static
     */
    public function maxLength(int $length): static
    {
        $this->maxLength = $length;
        return $this;
    }

    /**
     * Set minimum required length
     *
     * @param int $length Minimum number of characters required
     * @return static
     */
    public function minLength(int $length): static
    {
        $this->minLength = $length;
        return $this;
    }

    /**
     * Set regular expression pattern for validation
     *
     * @param string $pattern Regular expression pattern (e.g., '^[a-zA-Z0-9_]+$')
     * @return static
     */
    public function pattern(string $pattern): static
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * Set input type to email
     *
     * @return static
     */
    public function email(): static
    {
        $this->inputType = 'email';
        return $this;
    }

    /**
     * Set input type to URL
     *
     * @return static
     */
    public function url(): static
    {
        $this->inputType = 'url';
        return $this;
    }

    /**
     * Set input type to tel (telephone)
     *
     * @return static
     */
    public function tel(): static
    {
        $this->inputType = 'tel';
        return $this;
    }

    /**
     * Set input type to number
     *
     * @return static
     */
    public function number(): static
    {
        $this->inputType = 'number';
        return $this;
    }

    /**
     * Set prefix text shown before input (e.g., $, %, @)
     *
     * @param string $prefix Prefix text
     * @return static
     */
    public function prefix(string $prefix): static
    {
        $this->prefixText = $prefix;
        return $this;
    }

    /**
     * Get prefix text
     *
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefixText;
    }

    /**
     * Set suffix text shown after input (e.g., .00, %, km)
     *
     * @param string $suffix Suffix text
     * @return static
     */
    public function suffix(string $suffix): static
    {
        $this->suffixText = $suffix;
        return $this;
    }

    /**
     * Get suffix text
     *
     * @return string|null
     */
    public function getSuffix(): ?string
    {
        return $this->suffixText;
    }

    /**
     * Get validation attributes for this field.
     *
     * Returns validation-related properties that can be converted to Laravel rules
     * by FieldValidationRuleExtractor.
     *
     * @return array<string,mixed>
     */
    public function getValidationAttributes(): array
    {
        return [
            'min_length' => $this->minLength,
            'max_length' => $this->maxLength,
            'pattern' => $this->pattern,
        ];
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data with maxLength, minLength, pattern, type, prefix, suffix
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'maxLength' => $this->maxLength,
            'minLength' => $this->minLength,
            'pattern'   => $this->pattern,
            'type'      => $this->inputType,
            'prefix'    => $this->prefixText,
            'suffix'    => $this->suffixText,
        ]);
    }
}

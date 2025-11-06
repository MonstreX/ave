<?php

namespace Monstrex\Ave\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Number;
use Monstrex\Ave\Core\Fields\Textarea;
use Monstrex\Ave\Core\Validation\FieldValidationRuleExtractor;

class FieldValidationRuleExtractorTest extends TestCase
{
    public function test_extracts_text_input_min_length(): void
    {
        $field = TextInput::make('title')->minLength(10);
        $rules = FieldValidationRuleExtractor::extract($field, []);

        $this->assertContains('min:10', $rules);
    }

    public function test_extracts_text_input_max_length(): void
    {
        $field = TextInput::make('title')->maxLength(255);
        $rules = FieldValidationRuleExtractor::extract($field, []);

        $this->assertContains('max:255', $rules);
    }

    public function test_extracts_text_input_pattern(): void
    {
        $field = TextInput::make('title')->pattern('[A-Za-z0-9]+');
        $rules = FieldValidationRuleExtractor::extract($field, []);

        $this->assertContains('regex:/[A-Za-z0-9]+/', $rules);
    }

    public function test_extracts_number_min(): void
    {
        $field = Number::make('rating')->min(1);
        $rules = FieldValidationRuleExtractor::extract($field, []);

        $this->assertContains('min:1', $rules);
    }

    public function test_extracts_number_max(): void
    {
        $field = Number::make('rating')->max(5);
        $rules = FieldValidationRuleExtractor::extract($field, []);

        $this->assertContains('max:5', $rules);
    }

    public function test_extracts_textarea_max_length(): void
    {
        $field = Textarea::make('description')->maxLength(500);
        $rules = FieldValidationRuleExtractor::extract($field, []);

        $this->assertContains('max:500', $rules);
    }

    public function test_preserves_existing_rules(): void
    {
        $field = TextInput::make('title')->minLength(10);
        $existing = ['string', 'required'];
        $rules = FieldValidationRuleExtractor::extract($field, $existing);

        $this->assertContains('string', $rules);
        $this->assertContains('required', $rules);
        $this->assertContains('min:10', $rules);
    }
}

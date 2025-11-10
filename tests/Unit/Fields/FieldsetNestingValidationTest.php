<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Row;
use Monstrex\Ave\Core\Col;
use Monstrex\Ave\Exceptions\FieldsetNestingException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Fieldset nesting validation
 */
class FieldsetNestingValidationTest extends TestCase
{
    /**
     * Test that directly nested Fieldset throws exception
     */
    public function test_nested_fieldset_direct_throws_exception(): void
    {
        $this->expectException(FieldsetNestingException::class);
        $this->expectExceptionMessage('Fieldset nesting is not allowed');

        Fieldset::make('outer')->schema([
            TextInput::make('title'),
            Fieldset::make('inner')->schema([
                TextInput::make('name'),
            ]),
        ]);
    }

    /**
     * Test that normal Fieldset configuration works fine
     */
    public function test_normal_fieldset_configuration_works(): void
    {
        $fieldset = Fieldset::make('items')->schema([
            TextInput::make('title'),
            TextInput::make('description'),
        ]);

        $this->assertNotNull($fieldset);
        $this->assertEquals('items', $fieldset->key());
        $this->assertCount(2, $fieldset->getChildSchema());
    }

}

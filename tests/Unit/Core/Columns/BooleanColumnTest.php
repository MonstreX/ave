<?php

namespace Monstrex\Ave\Tests\Unit\Core\Columns;

use Monstrex\Ave\Core\Columns\BooleanColumn;
use PHPUnit\Framework\TestCase;

class BooleanColumnTest extends TestCase
{
    public function test_boolean_column_toggle_configuration(): void
    {
        $column = BooleanColumn::make('active')
            ->trueValue(1)
            ->falseValue(0)
            ->inlineToggle();

        $this->assertTrue($column->supportsInline());
        $this->assertEquals('toggle', $column->inlineMode());
        $this->assertTrue($column->isToggleEnabled());
        $this->assertEquals('required|in:1,0', $column->inlineValidationRules());
    }

    public function test_boolean_column_active_detection(): void
    {
        $column = BooleanColumn::make('active')->trueValue('yes')->falseValue('no');

        $this->assertTrue($column->isActive('yes'));
        $this->assertFalse($column->isActive('no'));
    }
}


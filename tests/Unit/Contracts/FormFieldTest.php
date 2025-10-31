<?php

namespace Monstrex\Ave\Tests\Unit\Contracts;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Contracts\FormField;

class FormFieldTest extends TestCase
{
    public function test_form_field_interface_exists()
    {
        $this->assertTrue(interface_exists(FormField::class));
    }

    public function test_form_field_has_required_methods()
    {
        $reflection = new \ReflectionClass(FormField::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($m) => $m->getName(), $methods);

        $this->assertContains('key', $methodNames);
        $this->assertContains('getLabel', $methodNames);
        $this->assertContains('isRequired', $methodNames);
        $this->assertContains('toArray', $methodNames);
        $this->assertContains('extract', $methodNames);
    }
}

<?php

namespace Monstrex\Ave\Tests\Unit\Contracts;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Contracts\FormField;
use Monstrex\Ave\Contracts\Authorizable;
use Monstrex\Ave\Contracts\Discoverable;
use Monstrex\Ave\Contracts\Renderable;
use Monstrex\Ave\Contracts\Persistable;

class ContractTest extends TestCase
{
    /**
     * Test that all required contracts are defined
     */
    public function test_all_contracts_exist()
    {
        $contracts = [
            FormField::class,
            Authorizable::class,
            Discoverable::class,
            Renderable::class,
            Persistable::class,
        ];

        foreach ($contracts as $contract) {
            $this->assertTrue(
                interface_exists($contract),
                "Contract {$contract} should exist"
            );
        }
    }

    /**
     * Test FormField contract has correct methods
     */
    public function test_form_field_interface_structure()
    {
        $reflection = new \ReflectionClass(FormField::class);
        $methods = $reflection->getMethods();

        $this->assertCount(5, $methods, 'FormField should have 5 methods');
    }

    /**
     * Test Authorizable contract has correct methods
     */
    public function test_authorizable_interface_structure()
    {
        $reflection = new \ReflectionClass(Authorizable::class);
        $methods = $reflection->getMethods();

        $this->assertCount(1, $methods, 'Authorizable should have 1 method');
        $this->assertTrue(
            $reflection->hasMethod('authorize'),
            'Authorizable should have authorize method'
        );
    }

    /**
     * Test Discoverable contract has correct methods
     */
    public function test_discoverable_interface_structure()
    {
        $reflection = new \ReflectionClass(Discoverable::class);
        $methods = $reflection->getMethods();

        $this->assertCount(2, $methods, 'Discoverable should have 2 methods');
        $this->assertTrue($reflection->hasMethod('getSlug'));
        $this->assertTrue($reflection->hasMethod('getLabel'));
    }

    /**
     * Test Renderable contract has correct methods
     */
    public function test_renderable_interface_structure()
    {
        $reflection = new \ReflectionClass(Renderable::class);
        $methods = $reflection->getMethods();

        $this->assertCount(1, $methods, 'Renderable should have 1 method');
        $this->assertTrue($reflection->hasMethod('render'));
    }

    /**
     * Test Persistable contract has correct methods
     */
    public function test_persistable_interface_structure()
    {
        $reflection = new \ReflectionClass(Persistable::class);
        $methods = $reflection->getMethods();

        $this->assertCount(3, $methods, 'Persistable should have 3 methods');
        $this->assertTrue($reflection->hasMethod('create'));
        $this->assertTrue($reflection->hasMethod('update'));
        $this->assertTrue($reflection->hasMethod('delete'));
    }
}

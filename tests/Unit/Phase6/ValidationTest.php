<?php

namespace Monstrex\Ave\Tests\Unit\Phase6;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Fields\TextInput;

class ValidationTest extends TestCase
{
    public function test_form_validator_can_be_created()
    {
        $form = Form::make();
        $validator = new FormValidator($form);

        $this->assertInstanceOf(FormValidator::class, $validator);
    }

    public function test_form_validator_set_data()
    {
        $form = Form::make();
        $validator = new FormValidator($form);

        $result = $validator->setData(['name' => 'John']);
        $this->assertInstanceOf(FormValidator::class, $result);
    }

    public function test_form_validator_fluent_interface()
    {
        $form = Form::make();
        $validator = new FormValidator($form);

        $result = $validator->setData(['name' => 'John']);
        $this->assertInstanceOf(FormValidator::class, $result);
    }

    public function test_form_validator_validate_empty_form()
    {
        $form = Form::make()->fields([]);
        $validator = new FormValidator($form);

        $result = $validator->setData([])->validate();
        $this->assertTrue($result);
    }

    public function test_form_validator_passes()
    {
        $form = Form::make()->fields([]);
        $validator = new FormValidator($form);
        $validator->setData([])->validate();

        $this->assertTrue($validator->passes());
    }

    public function test_form_validator_fails()
    {
        $form = Form::make()->fields([]);
        $validator = new FormValidator($form);
        $validator->setData([])->validate();

        $this->assertFalse($validator->fails());
    }

    public function test_form_validator_get_errors()
    {
        $form = Form::make()->fields([]);
        $validator = new FormValidator($form);
        $validator->setData([])->validate();

        $errors = $validator->getErrors();
        $this->assertIsArray($errors);
    }
}

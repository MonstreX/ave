<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormRow;
use Monstrex\Ave\Core\FormColumn;

class FormTest extends TestCase
{
    protected Form $form;

    protected function setUp(): void
    {
        $this->form = Form::make();
    }

    public function test_form_can_be_instantiated()
    {
        $this->assertInstanceOf(Form::class, $this->form);
    }

    public function test_form_make_returns_new_instance()
    {
        $form1 = Form::make();
        $form2 = Form::make();
        $this->assertNotSame($form1, $form2);
    }

    public function test_form_fluent_interface()
    {
        $result = $this->form->submitLabel('Save')->cancelUrl('/back');
        $this->assertInstanceOf(Form::class, $result);
    }

    public function test_form_add_row()
    {
        $row = FormRow::make();
        $this->form->addRow($row);

        $rows = $this->form->getAllFields();
        $this->assertIsArray($rows);
    }

    public function test_form_fields_helper()
    {
        $this->form->fields(['field1', 'field2']);
        $allFields = $this->form->getAllFields();

        $this->assertCount(2, $allFields);
    }

    public function test_form_get_all_fields()
    {
        $column = FormColumn::make()->fields(['name', 'email']);
        $row = FormRow::make()->columns([$column]);
        $this->form->addRow($row);

        $fields = $this->form->getAllFields();
        $this->assertCount(2, $fields);
    }
}

<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Textarea;
use Monstrex\Ave\Core\Fields\Number;
use Monstrex\Ave\Core\Fields\Hidden;
use Monstrex\Ave\Core\Fields\Select;
use Monstrex\Ave\Core\Fields\Toggle;
use Monstrex\Ave\Core\Fields\Checkbox;
use Monstrex\Ave\Core\Fields\RadioGroup;
use Monstrex\Ave\Core\Fields\PasswordInput;
use Monstrex\Ave\Core\Fields\File;
use Monstrex\Ave\Core\Fields\ColorPicker;
use Monstrex\Ave\Core\Fields\Tags;
use Monstrex\Ave\Core\Fields\DateTimePicker;
use Monstrex\Ave\Core\Fields\RichEditor;
use Monstrex\Ave\Core\Fields\CodeEditor;
use PHPUnit\Framework\TestCase;

class BasicFieldsTest extends TestCase
{
    /**
     * Test TextInput field
     */
    public function test_text_input_configuration(): void
    {
        $field = TextInput::make('username')
            ->label('Username')
            ->required()
            ->minLength(3)
            ->maxLength(50)
            ->pattern('^[a-zA-Z0-9_]+$');

        $this->assertEquals('username', $field->key());
        $this->assertEquals('Username', $field->getLabel());
        $this->assertTrue($field->isRequired());
        $this->assertEquals(3, $field->toArray()['minLength']);
        $this->assertEquals(50, $field->toArray()['maxLength']);
        $this->assertEquals('^[a-zA-Z0-9_]+$', $field->toArray()['pattern']);
    }

    /**
     * Test TextInput rules
     */
    public function test_text_input_rules(): void
    {
        $field = TextInput::make('username')
            ->required()
            ->rules(['required', 'string', 'unique:users']);

        $rules = $field->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('string', $rules);
        $this->assertContains('unique:users', $rules);
    }

    /**
     * Test TextInput email variant
     */
    public function test_text_input_email_variant(): void
    {
        $field = TextInput::make('email')
            ->email()
            ->required();

        $array = $field->toArray();
        $this->assertEquals('email', $array['type']);
    }

    /**
     * Test TextInput url variant
     */
    public function test_text_input_url_variant(): void
    {
        $field = TextInput::make('website')
            ->url();

        $array = $field->toArray();
        $this->assertEquals('url', $array['type']);
    }

    /**
     * Test TextInput tel variant
     */
    public function test_text_input_tel_variant(): void
    {
        $field = TextInput::make('phone')
            ->tel();

        $array = $field->toArray();
        $this->assertEquals('tel', $array['type']);
    }

    /**
     * Test TextInput number variant with prefix and suffix
     */
    public function test_text_input_number_with_affix(): void
    {
        $field = TextInput::make('price')
            ->number()
            ->prefix('$')
            ->suffix('.00');

        $array = $field->toArray();
        $this->assertEquals('number', $array['type']);
        $this->assertEquals('$', $array['prefix']);
        $this->assertEquals('.00', $array['suffix']);
        $this->assertEquals('$', $field->getPrefix());
        $this->assertEquals('.00', $field->getSuffix());
    }

    /**
     * Test TextInput chainability with type and affix
     */
    public function test_text_input_chainability_with_variants(): void
    {
        $field = TextInput::make('distance')
            ->label('Distance')
            ->url()
            ->suffix('km')
            ->required();

        $this->assertEquals('distance', $field->key());
        $this->assertEquals('Distance', $field->getLabel());
        $this->assertEquals('url', $field->toArray()['type']);
        $this->assertEquals('km', $field->getSuffix());
        $this->assertTrue($field->isRequired());
    }

    /**
     * Test Textarea field
     */
    public function test_textarea_configuration(): void
    {
        $field = Textarea::make('description')
            ->label('Description')
            ->rows(5)
            ->maxLength(1000)
            ->required();

        $this->assertEquals('description', $field->key());
        $this->assertEquals('Description', $field->getLabel());
        $this->assertEquals(5, $field->toArray()['rows']);
        $this->assertEquals(1000, $field->toArray()['maxLength']);
        $this->assertTrue($field->isRequired());
    }

    /**
     * Test Textarea validation rules
     */
    public function test_textarea_validation_rules(): void
    {
        $field = Textarea::make('bio')
            ->required()
            ->rules(['required', 'min:10', 'max:500']);

        $rules = $field->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('min:10', $rules);
        $this->assertContains('max:500', $rules);
    }

    /**
     * Test Number field
     */
    public function test_number_configuration(): void
    {
        $field = Number::make('age')
            ->label('Age')
            ->min(0)
            ->max(150)
            ->step(1)
            ->required();

        $this->assertEquals('age', $field->key());
        $this->assertEquals('Age', $field->getLabel());
        $this->assertEquals(0, $field->toArray()['min']);
        $this->assertEquals(150, $field->toArray()['max']);
        $this->assertEquals(1, $field->toArray()['step']);
    }

    /**
     * Test Number field type conversion
     */
    public function test_number_extract_converts_to_float(): void
    {
        $field = Number::make('price');

        $this->assertSame(19.99, $field->extract('19.99'));
        $this->assertSame(100.0, $field->extract(100));
        $this->assertSame(42.5, $field->extract('42.5'));
        $this->assertNull($field->extract(null));
        $this->assertNull($field->extract(''));
    }

    /**
     * Test Number validation rules
     */
    public function test_number_validation_rules(): void
    {
        $field = Number::make('quantity')
            ->required()
            ->rules(['required', 'min:1', 'max:999', 'numeric']);

        $rules = $field->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('min:1', $rules);
        $this->assertContains('max:999', $rules);
        $this->assertContains('numeric', $rules);
    }

    /**
     * Test Hidden field
     */
    public function test_hidden_field(): void
    {
        $field = Hidden::make('user_id')
            ->default(123);

        $this->assertEquals('user_id', $field->key());
        // Hidden field should have default value in toArray
        $this->assertEquals(123, $field->toArray()['default']);
    }

    /**
     * Test Hidden field has no validation by default
     */
    public function test_hidden_field_validation(): void
    {
        $field = Hidden::make('token');

        $rules = $field->getRules();

        // Hidden fields typically have no rules
        $this->assertEmpty($rules);
    }

    /**
     * Test Select field
     */
    public function test_select_configuration(): void
    {
        $options = [
            'red' => 'Red',
            'green' => 'Green',
            'blue' => 'Blue',
        ];

        $field = Select::make('color')
            ->label('Color')
            ->options($options)
            ->required();

        $this->assertEquals('color', $field->key());
        $this->assertEquals('Color', $field->getLabel());
        $this->assertEquals($options, $field->toArray()['options']);
        $this->assertFalse($field->toArray()['multiple']);
    }

    /**
     * Test Select field with multiple
     */
    public function test_select_multiple(): void
    {
        $field = Select::make('tags')
            ->options(['tag1' => 'Tag 1', 'tag2' => 'Tag 2'])
            ->multiple(true);

        $this->assertTrue($field->toArray()['multiple']);
    }

    /**
     * Test Select validation
     */
    public function test_select_validation_rules(): void
    {
        $field = Select::make('status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->required()
            ->rules(['required', 'in:active,inactive']);

        $rules = $field->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('in:active,inactive', $rules);
    }

    /**
     * Test Toggle field
     */
    public function test_toggle_configuration(): void
    {
        $field = Toggle::make('is_published')
            ->label('Published')
            ->default(false);

        $this->assertEquals('is_published', $field->key());
        $this->assertEquals('Published', $field->getLabel());
        $this->assertFalse($field->toArray()['default']);
    }

    /**
     * Test Toggle field type conversion
     */
    public function test_toggle_extract_converts_to_boolean(): void
    {
        $field = Toggle::make('active');

        $this->assertTrue($field->extract('on'));
        $this->assertTrue($field->extract('1'));
        $this->assertTrue($field->extract(1));
        $this->assertTrue($field->extract(true));
        $this->assertFalse($field->extract('off'));
        $this->assertFalse($field->extract('0'));
        $this->assertFalse($field->extract(0));
        $this->assertFalse($field->extract(false));
        $this->assertFalse($field->extract(null));
    }

    /**
     * Test Checkbox field
     */
    public function test_checkbox_configuration(): void
    {
        $field = Checkbox::make('is_published')
            ->label('Published')
            ->checkboxLabel('Mark as published')
            ->default(false);

        $this->assertEquals('is_published', $field->key());
        $this->assertEquals('Published', $field->getLabel());
        $this->assertEquals('Mark as published', $field->getCheckboxLabel());
        $this->assertFalse($field->toArray()['default']);
    }

    /**
     * Test Checkbox field type conversion
     */
    public function test_checkbox_extract_converts_to_integer(): void
    {
        $field = Checkbox::make('active');

        $this->assertSame(1, $field->extract('on'));
        $this->assertSame(1, $field->extract('1'));
        $this->assertSame(1, $field->extract(1));
        $this->assertSame(1, $field->extract(true));
        $this->assertSame(0, $field->extract('off'));
        $this->assertSame(0, $field->extract('0'));
        $this->assertSame(0, $field->extract(0));
        $this->assertSame(0, $field->extract(false));
        $this->assertSame(0, $field->extract(null));
    }

    /**
     * Test Checkbox validation
     */
    public function test_checkbox_validation_rules(): void
    {
        $field = Checkbox::make('agree_to_terms')
            ->checkboxLabel('I agree')
            ->required()
            ->rules(['required', 'accepted']);

        $rules = $field->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('accepted', $rules);
    }

    /**
     * Test RadioGroup field
     */
    public function test_radio_group_configuration(): void
    {
        $options = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'draft' => 'Draft',
        ];

        $field = RadioGroup::make('status')
            ->label('Status')
            ->options($options)
            ->default('draft');

        $this->assertEquals('status', $field->key());
        $this->assertEquals('Status', $field->getLabel());
        $this->assertEquals($options, $field->getOptions());
        $this->assertEquals('draft', $field->toArray()['default']);
        $this->assertFalse($field->isInline());
    }

    /**
     * Test RadioGroup inline layout
     */
    public function test_radio_group_inline(): void
    {
        $field = RadioGroup::make('gender')
            ->options(['male' => 'Male', 'female' => 'Female'])
            ->inline();

        $this->assertTrue($field->isInline());
        $this->assertTrue($field->toArray()['inline']);
    }

    /**
     * Test RadioGroup value extraction
     */
    public function test_radio_group_extract_returns_value(): void
    {
        $field = RadioGroup::make('status');

        $this->assertSame('active', $field->extract('active'));
        $this->assertSame('draft', $field->extract('draft'));
        $this->assertNull($field->extract(''));
        $this->assertNull($field->extract(null));
    }

    /**
     * Test RadioGroup validation
     */
    public function test_radio_group_validation_rules(): void
    {
        $field = RadioGroup::make('role')
            ->options(['admin' => 'Admin', 'user' => 'User'])
            ->required()
            ->rules(['required', 'in:admin,user']);

        $rules = $field->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('in:admin,user', $rules);
    }

    /**
     * Test PasswordInput field configuration
     */
    public function test_password_input_configuration(): void
    {
        $field = PasswordInput::make('password')
            ->label('Password')
            ->minLength(8)
            ->maxLength(50)
            ->required();

        $this->assertEquals('password', $field->key());
        $this->assertEquals('Password', $field->getLabel());
        $this->assertTrue($field->isRequired());
        $this->assertTrue($field->hasVisibilityToggle());
        $this->assertEquals(8, $field->toArray()['minLength']);
        $this->assertEquals(50, $field->toArray()['maxLength']);
    }

    /**
     * Test PasswordInput with toggle disabled
     */
    public function test_password_input_toggle_disabled(): void
    {
        $field = PasswordInput::make('pin')
            ->showToggle(false)
            ->minLength(4);

        $this->assertFalse($field->hasVisibilityToggle());
        $this->assertFalse($field->toArray()['showToggle']);
    }

    /**
     * Test PasswordInput confirmation field
     */
    public function test_password_input_confirmation(): void
    {
        $field = PasswordInput::make('password_confirmation')
            ->label('Confirm Password')
            ->confirmation();

        $this->assertTrue($field->isConfirmationField());
        $this->assertTrue($field->toArray()['isConfirmation']);
    }

    /**
     * Test PasswordInput validation rules
     */
    public function test_password_input_validation_rules(): void
    {
        $field = PasswordInput::make('password')
            ->required()
            ->minLength(8)
            ->rules(['required', 'min:8', 'confirmed']);

        $rules = $field->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('min:8', $rules);
        $this->assertContains('confirmed', $rules);
    }

    /**
     * Test File field single upload
     */
    public function test_file_field_single_upload(): void
    {
        $field = File::make('document')
            ->label('Document')
            ->maxFileSize(5120)
            ->accept(['application/pdf']);

        $this->assertEquals('document', $field->key());
        $this->assertEquals('Document', $field->getLabel());
        $this->assertFalse($field->isMultiple());
        $this->assertEquals(5120, $field->getMaxFileSize());
        $this->assertEquals(['application/pdf'], $field->getAcceptedMimes());
    }

    /**
     * Test File field multiple upload
     */
    public function test_file_field_multiple_upload(): void
    {
        $field = File::make('attachments')
            ->multiple(true)
            ->maxFiles(5)
            ->minFiles(1)
            ->maxFileSize(10240);

        $this->assertTrue($field->isMultiple());
        $this->assertEquals(5, $field->getMaxFiles());
        $this->assertEquals(1, $field->getMinFiles());
        $this->assertEquals(10240, $field->getMaxFileSize());
    }

    /**
     * Test ColorPicker field configuration
     */
    public function test_color_picker_configuration(): void
    {
        $field = ColorPicker::make('brand_color')
            ->label('Brand Color')
            ->default('#0cb7e0');

        $this->assertEquals('brand_color', $field->key());
        $this->assertEquals('Brand Color', $field->getLabel());
        $this->assertEquals('#0cb7e0', $field->toArray()['default']);
    }

    /**
     * Test ColorPicker with palette
     */
    public function test_color_picker_with_palette(): void
    {
        $palette = ['#ff0000', '#00ff00', '#0000ff'];
        $field = ColorPicker::make('accent_color')
            ->palette($palette);

        $this->assertEquals($palette, $field->getPalette());
        $this->assertEquals($palette, $field->toArray()['colorPalette']);
    }

    /**
     * Test ColorPicker value extraction
     */
    public function test_color_picker_extract_normalizes_hex(): void
    {
        $field = ColorPicker::make('color');

        $this->assertEquals('#ff0000', $field->extract('#FF0000')); // Lowercase conversion
        $this->assertEquals('#0cb7e0', $field->extract('#0cb7e0'));
        $this->assertEquals('#ffffff', $field->extract('FFFFFF')); // Add # if missing
        $this->assertNull($field->extract(''));
        $this->assertNull($field->extract(null));
    }

    /**
     * Test Tags field configuration
     */
    public function test_tags_field_configuration(): void
    {
        $suggestions = ['laravel', 'php', 'javascript'];
        $field = Tags::make('tags')
            ->label('Tags')
            ->separator(',')
            ->suggestions($suggestions);

        $this->assertEquals('tags', $field->key());
        $this->assertEquals('Tags', $field->getLabel());
        $this->assertEquals(',', $field->getSeparator());
        $this->assertEquals($suggestions, $field->getSuggestions());
        $this->assertFalse($field->allowsDuplicates());
    }

    /**
     * Test Tags field parsing comma-separated string
     */
    public function test_tags_extract_parses_string(): void
    {
        $field = Tags::make('tags');

        $result = $field->extract('php, laravel, vue');
        $this->assertEquals(['php', 'laravel', 'vue'], $result);
    }

    /**
     * Test Tags field removes duplicates
     */
    public function test_tags_extract_removes_duplicates(): void
    {
        $field = Tags::make('tags')->allowDuplicates(false);

        $result = $field->extract('php, laravel, php, vue, laravel');
        $this->assertCount(3, $result);
        $this->assertTrue(in_array('php', $result));
    }

    /**
     * Test Tags field trims whitespace
     */
    public function test_tags_extract_trims_whitespace(): void
    {
        $field = Tags::make('tags');

        $result = $field->extract('  php  ,  laravel  ,  vue  ');
        $this->assertEquals(['php', 'laravel', 'vue'], $result);
    }

    /**
     * Test Toggle validation
     */
    public function test_toggle_validation_rules(): void
    {
        $field = Toggle::make('agree_to_terms')
            ->required()
            ->rules(['required', 'accepted']);

        $rules = $field->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('accepted', $rules);
    }

    /**
     * Test DateTimePicker field
     */
    public function test_date_time_picker_configuration(): void
    {
        $field = DateTimePicker::make('event_date')
            ->label('Event Date')
            ->withTime(true)
            ->minDate('2024-01-01')
            ->maxDate('2024-12-31');

        $this->assertEquals('event_date', $field->key());
        $this->assertEquals('Event Date', $field->getLabel());
        $this->assertTrue($field->toArray()['withTime']);
        $this->assertEquals('2024-01-01', $field->toArray()['minDate']);
        $this->assertEquals('2024-12-31', $field->toArray()['maxDate']);
    }

    /**
     * Test DateTimePicker without time
     */
    public function test_date_time_picker_without_time(): void
    {
        $field = DateTimePicker::make('birth_date')
            ->withTime(false);

        $this->assertFalse($field->toArray()['withTime']);
    }

    /**
     * Test DateTimePicker validation
     */
    public function test_date_time_picker_validation_rules(): void
    {
        $field = DateTimePicker::make('created_at')
            ->required()
            ->withTime(true)
            ->rules(['required', 'date_format:Y-m-d H:i']);

        $rules = $field->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('date_format:Y-m-d H:i', $rules);
    }

    /**
     * Test field chainability
     */
    public function test_field_chainability(): void
    {
        $field = TextInput::make('name')
            ->label('Full Name')
            ->required()
            ->minLength(2)
            ->maxLength(100)
            ->placeholder('Enter your name')
            ->help('Your full legal name');

        $this->assertEquals('name', $field->key());
        $this->assertEquals('Full Name', $field->getLabel());
        $this->assertTrue($field->isRequired());
    }

    /**
     * Test nullable fields
     */
    public function test_nullable_field_validation(): void
    {
        $field = TextInput::make('middle_name')
            ->rules(['nullable']);

        $rules = $field->getRules();

        $this->assertContains('nullable', $rules);
    }

    /**
     * Test disabled field
     */
    public function test_disabled_field(): void
    {
        $field = TextInput::make('disabled_field')
            ->disabled(true);

        $this->assertTrue($field->toArray()['disabled']);
    }

    /**
     * Test field with custom rules
     */
    public function test_field_with_custom_rules(): void
    {
        $field = TextInput::make('custom_field')
            ->rules(['required', 'string', 'max:50']);

        $rules = $field->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('string', $rules);
        $this->assertContains('max:50', $rules);
    }

    /**
     * Test RichEditor field configuration
     */
    public function test_rich_editor_configuration(): void
    {
        $field = RichEditor::make('content')
            ->label('Content')
            ->height(500)
            ->toolbar('full')
            ->showMenuBar(true)
            ->maxLength(5000);

        $this->assertEquals('content', $field->key());
        $this->assertEquals('Content', $field->getLabel());
        $this->assertEquals(500, $field->getHeight());
        $this->assertEquals('full', $field->getToolbar());
        $this->assertTrue($field->hasMenuBar());
        $this->assertEquals(5000, $field->getMaxLength());
    }

    /**
     * Test RichEditor toolbar presets
     */
    public function test_rich_editor_toolbar_presets(): void
    {
        $minimalField = RichEditor::make('minimal_content')->toolbar('minimal');
        $basicField = RichEditor::make('basic_content')->toolbar('basic');
        $fullField = RichEditor::make('full_content')->toolbar('full');

        $this->assertEquals('minimal', $minimalField->getToolbar());
        $this->assertEquals('basic', $basicField->getToolbar());
        $this->assertEquals('full', $fullField->getToolbar());
    }

    /**
     * Test RichEditor feature enabling/disabling
     */
    public function test_rich_editor_features(): void
    {
        $field = RichEditor::make('content')
            ->features(['bold', 'italic', '-code']);

        $jsConfig = $field->getJsConfig();
        $this->assertIsArray($jsConfig);
        $this->assertArrayHasKey('buttons', $jsConfig);
    }

    /**
     * Test RichEditor feature enable/disable methods
     */
    public function test_rich_editor_enable_disable_features(): void
    {
        $field = RichEditor::make('content')
            ->features(['bold', 'italic'])
            ->enable(['underline'])
            ->disable(['code']);

        $jsConfig = $field->getJsConfig();
        $this->assertIsArray($jsConfig);
    }

    /**
     * Test RichEditor with options
     */
    public function test_rich_editor_options(): void
    {
        $options = ['upload.endpoint' => '/upload', 'uploader.insertImageAsBase64URI' => true];
        $field = RichEditor::make('content')->options($options);

        $jsConfig = $field->getJsConfig();
        $this->assertIsArray($jsConfig);
    }

    /**
     * Test RichEditor placeholder
     */
    public function test_rich_editor_placeholder(): void
    {
        $field = RichEditor::make('content')
            ->placeholder('Enter your HTML content here');

        $this->assertEquals('Enter your HTML content here', $field->getPlaceholder());
    }

    /**
     * Test RichEditor JS config includes height and toolbar
     */
    public function test_rich_editor_js_config(): void
    {
        $field = RichEditor::make('article_body')
            ->height(600)
            ->toolbar('basic')
            ->disable('images');

        $jsConfig = $field->getJsConfig();

        $this->assertEquals(600, $jsConfig['height']);
        $this->assertArrayHasKey('buttons', $jsConfig);
        $this->assertArrayHasKey('disablePlugins', $jsConfig);
        $this->assertIsArray($jsConfig['disablePlugins']);
    }

    /**
     * Test CodeEditor configuration
     */
    public function test_code_editor_configuration(): void
    {
        $field = CodeEditor::make('code')
            ->label('Code')
            ->height(400)
            ->language('javascript')
            ->theme('dark')
            ->lineNumbers(true)
            ->tabSize(4);

        $this->assertEquals('code', $field->key());
        $this->assertEquals('Code', $field->getLabel());
        $this->assertEquals(400, $field->getHeight());
        $this->assertEquals('javascript', $field->getLanguage());
        $this->assertEquals('dark', $field->getTheme());
        $this->assertTrue($field->hasLineNumbers());
        $this->assertEquals(4, $field->getTabSize());
    }

    /**
     * Test CodeEditor language modes
     */
    public function test_code_editor_language_modes(): void
    {
        $languages = ['html', 'css', 'javascript', 'json', 'xml'];

        foreach ($languages as $lang) {
            $field = CodeEditor::make('code')->language($lang);
            $this->assertEquals($lang, $field->getLanguage());
        }
    }

    /**
     * Test CodeEditor theme support
     */
    public function test_code_editor_theme_support(): void
    {
        $themes = ['light', 'dark', 'monokai'];

        foreach ($themes as $theme) {
            $field = CodeEditor::make('code')->theme($theme);
            $this->assertEquals($theme, $field->getTheme());
        }
    }

    /**
     * Test CodeEditor features configuration
     */
    public function test_code_editor_features(): void
    {
        $field = CodeEditor::make('code')
            ->lineNumbers(true)
            ->codeFolding(true)
            ->autoComplete(true)
            ->autoHeight(false);

        $this->assertTrue($field->hasLineNumbers());
        $this->assertTrue($field->hasCodeFolding());
        $this->assertTrue($field->hasAutoComplete());
        $this->assertFalse($field->hasAutoHeight());
    }

    /**
     * Test CodeEditor to array output
     */
    public function test_code_editor_to_array(): void
    {
        $field = CodeEditor::make('code')
            ->language('json')
            ->height(500)
            ->theme('dark');

        $array = $field->toArray();

        $this->assertEquals('json', $array['language']);
        $this->assertEquals(500, $array['height']);
        $this->assertEquals('dark', $array['theme']);
        $this->assertArrayHasKey('lineNumbers', $array);
        $this->assertArrayHasKey('codeFolding', $array);
        $this->assertArrayHasKey('autoComplete', $array);
        $this->assertArrayHasKey('tabSize', $array);
        $this->assertArrayHasKey('autoHeight', $array);
    }

    /**
     * Test CodeEditor tab size bounds
     */
    public function test_code_editor_tab_size_bounds(): void
    {
        $field1 = CodeEditor::make('code')->tabSize(0);  // Should be clamped to min 1
        $field2 = CodeEditor::make('code')->tabSize(10); // Should be clamped to max 8
        $field3 = CodeEditor::make('code')->tabSize(4);  // Valid value

        $this->assertGreaterThanOrEqual(1, $field1->getTabSize());
        $this->assertLessThanOrEqual(8, $field2->getTabSize());
        $this->assertEquals(4, $field3->getTabSize());
    }

    /**
     * Test CodeEditor chainability
     */
    public function test_code_editor_chainability(): void
    {
        $field = CodeEditor::make('script')
            ->label('JavaScript Code')
            ->language('javascript')
            ->height(600)
            ->theme('monokai')
            ->lineNumbers(true)
            ->codeFolding(true)
            ->autoComplete(true)
            ->tabSize(2);

        $this->assertEquals('script', $field->key());
        $this->assertEquals('JavaScript Code', $field->getLabel());
        $this->assertEquals('javascript', $field->getLanguage());
        $this->assertEquals(600, $field->getHeight());
    }

    /**
     * Test CodeEditor JSON value conversion
     */
    public function test_code_editor_json_value_conversion(): void
    {
        $field = CodeEditor::make('config');

        $arrayValue = ['key1' => 'value1', 'key2' => 'value2'];
        $field->setValue($arrayValue);

        $array = $field->toArray();
        $this->assertIsString($array['value']);
        $this->assertStringContainsString('key1', $array['value']);
    }

    /**
     * Test RichEditor height minimum constraint
     */
    public function test_rich_editor_minimum_height(): void
    {
        $field = RichEditor::make('content')->height(100); // Below 200px minimum

        $this->assertGreaterThanOrEqual(200, $field->getHeight());
    }

    /**
     * Test CodeEditor height minimum constraint
     */
    public function test_code_editor_minimum_height(): void
    {
        $field = CodeEditor::make('code')->height(100); // Below 200px minimum

        $this->assertGreaterThanOrEqual(200, $field->getHeight());
    }

    /**
     * Test RichEditor maxLength minimum constraint
     */
    public function test_rich_editor_max_length_minimum(): void
    {
        $field = RichEditor::make('content')->maxLength(50); // Below 100 character minimum

        $this->assertGreaterThanOrEqual(100, $field->getMaxLength());
    }
}

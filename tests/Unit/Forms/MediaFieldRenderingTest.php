<?php

namespace Monstrex\Ave\Tests\Unit\Forms;

use PHPUnit\Framework\TestCase;

class MediaFieldRenderingTest extends TestCase
{
    public function test_media_field_blade_uses_state_path_ids(): void
    {
        $blade = file_get_contents(__DIR__ . '/../../../resources/views/components/forms/fields/media.blade.php');
        $this->assertStringContainsString('data-field-name="{{ $fieldStatePath }}"', $blade);
        $this->assertStringContainsString('data-field-id="{{ $fieldInputId }}"', $blade);
        $this->assertStringContainsString('id="media-item-template-{{ $fieldInputId }}"', $blade);
    }
}

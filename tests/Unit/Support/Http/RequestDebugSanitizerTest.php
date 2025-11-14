<?php

namespace Monstrex\Ave\Tests\Unit\Support\Http;

use Monstrex\Ave\Support\Http\RequestDebugSanitizer;
use PHPUnit\Framework\TestCase;

class RequestDebugSanitizerTest extends TestCase
{
    public function test_sanitizer_drops_default_sensitive_keys(): void
    {
        $sanitizer = new RequestDebugSanitizer();

        $payload = [
            'name' => 'Ave',
            'password' => 'secret',
            'nested' => [
                'token' => 'abc',
                'profile' => ['api_token' => 'xyz'],
            ],
        ];

        $result = $sanitizer->sanitize($payload);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayHasKey('nested', $result);
        $this->assertArrayNotHasKey('token', $result['nested']);
        $this->assertArrayNotHasKey('api_token', $result['nested']['profile']);
    }

    public function test_sanitizer_removes_additional_sensitive_keys_with_bracket_notation(): void
    {
        $sanitizer = new RequestDebugSanitizer();

        $payload = [
            'fieldset' => [
                'items' => [
                    ['secret_code' => '123'],
                ],
            ],
        ];

        $result = $sanitizer->sanitize($payload, ['fieldset.items[0][secret_code]']);

        $this->assertArrayHasKey('fieldset', $result);
        $this->assertArrayHasKey('items', $result['fieldset']);
        $this->assertArrayHasKey(0, $result['fieldset']['items']);
        $this->assertArrayNotHasKey('secret_code', $result['fieldset']['items'][0]);
    }
}

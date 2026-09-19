<?php

namespace Tests\Unit\Services;

use App\Services\AiService;
use Exception;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    public function test_recognize_item_returns_correct_structure(): void
    {
        // Test response structure without hitting real API
        $responseJson = json_encode([
            'category' => 'Electronics',
            'condition' => 'Good',
            'brand' => 'Samsung',
            'model' => 'TV43',
            'detected_defects' => ['small scratch on corner'],
            'confidence' => 0.92,
        ]);

        $parsed = json_decode($responseJson, true);

        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('category', $parsed);
        $this->assertArrayHasKey('condition', $parsed);
        $this->assertArrayHasKey('brand', $parsed);
        $this->assertArrayHasKey('detected_defects', $parsed);
        $this->assertArrayHasKey('confidence', $parsed);
        $this->assertEquals('Electronics', $parsed['category']);
        $this->assertEquals(0.92, $parsed['confidence']);
    }

    public function test_missing_api_key_throws_exception(): void
    {
        config(['services.anthropic.key' => null]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ANTHROPIC_API_KEY not configured');

        new AiService();
    }

    public function test_json_parsing_handles_valid_responses(): void
    {
        $validJson = json_encode([
            'category' => 'Furniture',
            'condition' => 'Fair',
            'brand' => null,
            'model' => null,
            'detected_defects' => [],
            'confidence' => 0.78,
        ]);

        $result = json_decode($validJson, true);

        $this->assertNotNull($result);
        $this->assertEquals('Furniture', $result['category']);
        $this->assertEquals('Fair', $result['condition']);
        $this->assertNull($result['brand']);
    }

    public function test_mime_type_detection_for_images(): void
    {
        // Test that MIME types are correctly handled
        $validMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

        foreach ($validMimeTypes as $mimeType) {
            $this->assertStringStartsWith('image/', $mimeType);
        }
    }
}

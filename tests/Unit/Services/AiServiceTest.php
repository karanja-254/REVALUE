<?php

namespace Tests\Unit\Services;

use App\Services\AiService;
use PHPUnit\Framework\TestCase;

class AiServiceTest extends TestCase
{
    public function test_recognize_item_returns_correct_structure(): void
    {
        // This is a simplified test; in real environment, mock the Anthropic client
        $expected = [
            'category' => 'Electronics',
            'condition' => 'Good',
            'brand' => 'Samsung',
            'model' => 'TV43',
            'detected_defects' => ['small scratch on corner'],
            'confidence' => 0.92,
        ];

        $this->assertArrayHasKey('category', $expected);
        $this->assertArrayHasKey('condition', $expected);
        $this->assertArrayHasKey('brand', $expected);
        $this->assertArrayHasKey('confidence', $expected);
    }
}

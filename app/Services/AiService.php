<?php

namespace App\Services;

use Anthropic\Anthropic;
use Exception;

class AiService
{
    private Anthropic $client;

    public function __construct()
    {
        $apiKey = config('services.anthropic.key');
        if (!$apiKey) {
            throw new Exception('ANTHROPIC_API_KEY not configured. AI recognition unavailable.');
        }
        $this->client = new Anthropic(apiKey: $apiKey);
    }

    /**
     * Recognize item from image using Claude Vision
     *
     * @param string $imageInput Image URL or base64-encoded image data
     * @param string $sellerDescription Seller's item description
     * @return array{category: string, condition: string, brand: ?string, model: ?string, detected_defects: array, confidence: float}
     * @throws Exception
     */
    public function recognizeItem(string $imageInput, string $sellerDescription): array
    {
        try {
            $prompt = "Analyze this item image and seller description. Return JSON with:
- category (e.g., Electronics, Furniture, Appliances)
- condition (one of: Mint, Excellent, Good, Fair, Poor)
- brand (extracted if visible, null otherwise)
- model (extracted if visible, null otherwise)
- detected_defects (array of strings describing visible damage/issues)
- confidence (0.0-1.0 how confident you are in the category and condition)

Seller description: $sellerDescription

Return ONLY valid JSON, no markdown or extra text.";

            $response = $this->client->messages()->create(
                model: 'claude-3-5-sonnet-20241022',
                max_tokens: 500,
                messages: [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => strpos($imageInput, 'http') === 0 ? 'url' : 'base64',
                                    'url' => strpos($imageInput, 'http') === 0 ? $imageInput : null,
                                    'media_type' => strpos($imageInput, 'http') === 0 ? 'image/jpeg' : 'image/jpeg',
                                    'data' => strpos($imageInput, 'http') === 0 ? null : $imageInput,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            );

            $content = $response->content[0]->text;
            $result = json_decode($content, true);

            if (!$result) {
                throw new Exception('Failed to parse Claude response as JSON');
            }

            return $result;
        } catch (Exception $e) {
            \Log::warning('AI recognition failed', ['error' => $e->getMessage()]);
            throw new Exception('AI recognition failed. Item queued for manual review.');
        }
    }
}

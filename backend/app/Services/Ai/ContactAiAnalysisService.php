<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactAiAnalysisService
{
    private const FALLBACK = [
        'category' => 'other',
        'sentiment' => 'neutral',
        'priority' => 'normal',
        'summary' => 'AI analysis fallback',
        'ai_available' => false,
    ];

    private const VALID_CATEGORIES = ['job_offer', 'question', 'collaboration', 'support', 'spam', 'other'];
    private const VALID_SENTIMENTS = ['positive', 'neutral', 'negative'];
    private const VALID_PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public function analyze(string $comment): array
    {
        if (!$this->isEnabled()) {
            return self::FALLBACK;
        }

        try {
            $response = Http::timeout(config('ai.timeout_seconds'))
                ->withToken(config('ai.api_key'))
                ->acceptJson()
                ->post(config('ai.base_url') . '/chat/completions', [
                    'model' => config('ai.model'),
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $this->userPrompt($comment)],
                    ],
                    'temperature' => 0.2,
                ]);

            if (!$response->successful()) {
                Log::warning('AI analysis failed', [
                    'status' => $response->status(),
                ]);
                return self::FALLBACK;
            }

            $parsed = $this->parseResponse($response->json());

            if ($parsed === null) {
                return self::FALLBACK;
            }

            $parsed['ai_available'] = true;

            return $parsed;
        } catch (\Throwable $e) {
            Log::warning('AI analysis failed', [
                'error' => $e->getMessage(),
            ]);
            return self::FALLBACK;
        }
    }

    private function isEnabled(): bool
    {
        if (!config('ai.enabled')) {
            return false;
        }
        if (empty(config('ai.api_key'))) {
            return false;
        }
        return true;
    }

    private function systemPrompt(): string
    {
        return 'You analyze contact form messages for a developer landing page. Return only valid JSON. Do not return markdown or explanations.';
    }

    private function userPrompt(string $comment): string
    {
        $comment = addcslashes($comment, '"');

        return <<<PROMPT
Analyze the contact form message for a developer landing page.

Return only valid JSON with this structure:
{
  "category": "job_offer | question | collaboration | support | spam | other",
  "sentiment": "positive | neutral | negative",
  "priority": "low | normal | high | urgent",
  "summary": "short summary in Russian, max 160 characters"
}

Rules:
- Do not add markdown.
- Do not add explanations.
- Use "spam" only for obvious spam, ads, scams, or meaningless messages.
- Use "urgent" only if the message clearly requires immediate attention.
- If unsure, use category "other", sentiment "neutral", priority "normal".

Message:
{$comment}
PROMPT;
    }

    private function parseResponse($body): ?array
    {
        if (!is_array($body)) {
            Log::warning('AI response body is not an array');
            return null;
        }

        $content = $body['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            Log::warning('AI response missing content');
            return null;
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            Log::warning('AI response is not valid JSON');
            return null;
        }

        return $this->validateFields($data);
    }

    private function validateFields(array $data): array
    {
        $category = in_array($data['category'] ?? '', self::VALID_CATEGORIES, true)
            ? $data['category']
            : self::FALLBACK['category'];

        $sentiment = in_array($data['sentiment'] ?? '', self::VALID_SENTIMENTS, true)
            ? $data['sentiment']
            : self::FALLBACK['sentiment'];

        $priority = in_array($data['priority'] ?? '', self::VALID_PRIORITIES, true)
            ? $data['priority']
            : self::FALLBACK['priority'];

        $summary = is_string($data['summary'] ?? null)
            ? mb_substr(trim($data['summary']), 0, 160)
            : self::FALLBACK['summary'];

        return [
            'category' => $category,
            'sentiment' => $sentiment,
            'priority' => $priority,
            'summary' => $summary,
        ];
    }
}

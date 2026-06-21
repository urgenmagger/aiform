<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAnalysisService
{
    private const FALLBACK = [
        'category' => 'other',
        'sentiment' => 'neutral',
        'priority' => 'normal',
        'summary' => 'AI analysis stub',
        'ai_available' => false,
    ];

    public function analyze(string $comment): array
    {
        $apiKey = env('DEEPSEEK_API_KEY');

        if (!$apiKey) {
            Log::warning('DEEPSEEK_API_KEY not set, using AI stub');
            return self::FALLBACK;
        }

        try {
            $response = Http::timeout(15)
                ->withToken($apiKey)
                ->post('https://api.deepseek.com/v1/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $comment],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                Log::warning('DeepSeek API returned non-successful status', [
                    'status' => $response->status(),
                    'body' => $response->body(),
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
            Log::warning('DeepSeek API call failed', [
                'error' => $e->getMessage(),
            ]);
            return self::FALLBACK;
        }
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are an assistant analyzing incoming contact form messages for a developer's landing page.
For each message, return a JSON object with the following fields:

- category: one of [job_offer, question, collaboration, support, spam, other]
- sentiment: one of [positive, neutral, negative]
- priority: one of [low, normal, high, urgent]
- summary: a single-sentence summary of the message in English (max 200 chars)

Rules:
- "job_offer" — the person is offering a job, project, or contract
- "question" — asking a question about services, tech stack, or experience
- "collaboration" — proposing partnership or cooperation
- "support" — asking for help, reporting a bug, or requesting support
- "spam" — unsolicited advertising, mass mail, irrelevant commercial offer
- "other" — none of the above
- If the message is in Russian, analyze it normally and write the summary in English
- Respond with ONLY the JSON object, no additional text.

Example:
{"category":"question","sentiment":"neutral","priority":"normal","summary":"Prospective client asks about website development cost and timeline"}
PROMPT;
    }

    private function parseResponse(array $body): ?array
    {
        $content = $body['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            Log::warning('DeepSeek response missing content');
            return null;
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            Log::warning('DeepSeek response is not valid JSON', ['content' => $content]);
            return null;
        }

        $validCategories = ['job_offer', 'question', 'collaboration', 'support', 'spam', 'other'];
        $validSentiments = ['positive', 'neutral', 'negative'];
        $validPriorities = ['low', 'normal', 'high', 'urgent'];

        if (!isset($data['category']) || !in_array($data['category'], $validCategories, true)) {
            $data['category'] = self::FALLBACK['category'];
        }
        if (!isset($data['sentiment']) || !in_array($data['sentiment'], $validSentiments, true)) {
            $data['sentiment'] = self::FALLBACK['sentiment'];
        }
        if (!isset($data['priority']) || !in_array($data['priority'], $validPriorities, true)) {
            $data['priority'] = self::FALLBACK['priority'];
        }
        if (!isset($data['summary']) || !is_string($data['summary'])) {
            $data['summary'] = self::FALLBACK['summary'];
        }

        $data['summary'] = mb_substr($data['summary'], 0, 500);

        return [
            'category' => $data['category'],
            'sentiment' => $data['sentiment'],
            'priority' => $data['priority'],
            'summary' => $data['summary'],
        ];
    }
}

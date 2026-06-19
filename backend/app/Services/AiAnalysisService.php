<?php

namespace App\Services;

class AiAnalysisService
{
    public function analyze(string $comment): array
    {
        return [
            'category' => 'other',
            'sentiment' => 'neutral',
            'priority' => 'normal',
            'summary' => 'AI analysis stub',
            'ai_available' => false,
        ];
    }
}

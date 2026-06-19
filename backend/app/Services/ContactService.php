<?php

namespace App\Services;

use App\Models\ContactRequest;

class ContactService
{
    public function __construct(
        private AiAnalysisService $aiAnalysis,
        private ContactMailService $mailService,
    ) {}

    public function handle(array $data, ?string $ip, ?string $userAgent): array
    {
        $contact = ContactRequest::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'comment' => $data['comment'],
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $aiResult = $this->aiAnalysis->analyze($contact->comment);

        $contact->update([
            'ai_category' => $aiResult['category'],
            'ai_sentiment' => $aiResult['sentiment'],
            'ai_priority' => $aiResult['priority'],
            'ai_summary' => $aiResult['summary'],
            'ai_available' => $aiResult['ai_available'],
        ]);

        $mailStatus = $this->mailService->sendAll($contact);

        $mailSent = $mailStatus['owner'] || $mailStatus['user'];

        return [
            'id' => $contact->id,
            'ai_analysis' => $aiResult,
            'mail_sent' => $mailSent,
        ];
    }
}

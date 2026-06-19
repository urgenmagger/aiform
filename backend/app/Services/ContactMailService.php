<?php

namespace App\Services;

use App\Mail\ContactOwnerMail;
use App\Mail\ContactUserCopyMail;
use App\Models\ContactRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactMailService
{
    public function sendOwnerNotification(ContactRequest $contact): bool
    {
        $ownerEmail = env('CONTACT_OWNER_EMAIL');

        if (!$ownerEmail) {
            Log::warning('CONTACT_OWNER_EMAIL not set, skipping owner notification', [
                'contact_id' => $contact->id,
            ]);
            return false;
        }

        try {
            Mail::to($ownerEmail)->send(new ContactOwnerMail($contact));
            Log::info('Owner notification sent', [
                'contact_id' => $contact->id,
                'to' => $ownerEmail,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send owner notification', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendUserCopy(ContactRequest $contact): bool
    {
        try {
            Mail::to($contact->email)->send(new ContactUserCopyMail($contact));
            Log::info('User copy sent', [
                'contact_id' => $contact->id,
                'to' => $contact->email,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send user copy', [
                'contact_id' => $contact->id,
                'to' => $contact->email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendAll(ContactRequest $contact): array
    {
        return [
            'owner' => $this->sendOwnerNotification($contact),
            'user' => $this->sendUserCopy($contact),
        ];
    }
}

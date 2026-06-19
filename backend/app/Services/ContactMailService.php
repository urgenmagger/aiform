<?php

namespace App\Services;

use App\Models\ContactRequest;
use Illuminate\Support\Facades\Log;

class ContactMailService
{
    public function sendOwnerNotification(ContactRequest $contact): void
    {
        Log::info('Mail stub: sendOwnerNotification', [
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'name' => $contact->name,
        ]);
    }

    public function sendUserCopy(ContactRequest $contact): void
    {
        Log::info('Mail stub: sendUserCopy', [
            'contact_id' => $contact->id,
            'email' => $contact->email,
        ]);
    }
}

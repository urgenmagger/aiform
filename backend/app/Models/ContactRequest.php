<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    protected $table = 'contact_requests';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'comment',
        'ai_category',
        'ai_sentiment',
        'ai_priority',
        'ai_summary',
        'ai_available',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'ai_available' => 'boolean',
    ];
}

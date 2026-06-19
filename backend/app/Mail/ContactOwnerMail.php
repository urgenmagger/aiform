<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Mail\Mailable;

class ContactOwnerMail extends Mailable
{
    public function __construct(
        public ContactRequest $contact,
    ) {}

    public function build(): self
    {
        $c = $this->contact;

        return $this
            ->subject('Новое обращение — ' . $c->name)
            ->html(<<<HTML
            <h2>Новое обращение с сайта</h2>
            <table>
                <tr><td><strong>ID:</strong></td><td>{$c->id}</td></tr>
                <tr><td><strong>Имя:</strong></td><td>{$c->name}</td></tr>
                <tr><td><strong>Телефон:</strong></td><td>{$c->phone}</td></tr>
                <tr><td><strong>Email:</strong></td><td>{$c->email}</td></tr>
                <tr><td><strong>Комментарий:</strong></td><td>{$c->comment}</td></tr>
                <tr><td><strong>IP:</strong></td><td>{$c->ip_address}</td></tr>
            </table>
            <hr>
            <p>AI-анализ: {$c->ai_summary} (категория: {$c->ai_category}, тональность: {$c->ai_sentiment})</p>
            HTML);
    }
}

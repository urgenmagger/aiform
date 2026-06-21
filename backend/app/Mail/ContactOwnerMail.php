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
        $aiAvailable = $c->ai_available ? 'да' : 'нет';

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
            <h3>AI-анализ</h3>
            <table>
                <tr><td><strong>Категория:</strong></td><td>{$c->ai_category}</td></tr>
                <tr><td><strong>Тональность:</strong></td><td>{$c->ai_sentiment}</td></tr>
                <tr><td><strong>Приоритет:</strong></td><td>{$c->ai_priority}</td></tr>
                <tr><td><strong>Резюме:</strong></td><td>{$c->ai_summary}</td></tr>
                <tr><td><strong>AI доступен:</strong></td><td>{$aiAvailable}</td></tr>
            </table>
            HTML);
    }
}

<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Mail\Mailable;

class ContactUserCopyMail extends Mailable
{
    public function __construct(
        public ContactRequest $contact,
    ) {}

    public function build(): self
    {
        $c = $this->contact;
        $appName = config('app.name');

        return $this
            ->subject('Ваше обращение принято — ' . $appName)
            ->html(<<<HTML
            <h2>{$c->name}, ваше обращение принято!</h2>
            <p>Спасибо за обращение. Мы свяжемся с вами в ближайшее время.</p>
            <table>
                <tr><td><strong>Номер обращения:</strong></td><td>#{$c->id}</td></tr>
                <tr><td><strong>Комментарий:</strong></td><td>{$c->comment}</td></tr>
            </table>
            <hr>
            <p>С уважением, команда {$appName}</p>
            HTML);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnonceGlobale extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $sujet,
        private readonly string $contenu,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->sujet)
            ->greeting("Bonjour {$notifiable->name},")
            ->line($this->contenu);
    }
}

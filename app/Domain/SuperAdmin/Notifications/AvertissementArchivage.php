<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AvertissementArchivage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $nomEcole) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Archivage imminent — {$this->nomEcole}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("« {$this->nomEcole} » est suspendue depuis 30 jours.")
            ->line("Sans régularisation, vos données seront archivées et l'accès définitivement coupé dans 30 jours.")
            ->line('Contactez notre équipe dès maintenant pour éviter cela.');
    }
}

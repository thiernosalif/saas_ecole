<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RappelAbonnement extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $nomEcole, private readonly int $joursRestants) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Rappel de paiement — {$this->nomEcole}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("L'abonnement de « {$this->nomEcole} » arrive à échéance dans {$this->joursRestants} jour(s).")
            ->line('Sans règlement, l\'accès à la plateforme sera automatiquement suspendu.');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaiementAbonnementEchoue extends Notification implements ShouldQueue
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
            ->subject("Échec de paiement — {$this->nomEcole}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("La tentative de paiement en ligne de l'abonnement de « {$this->nomEcole} » a échoué.")
            ->line('Vous pouvez réessayer à tout moment depuis le portail. Aucune suspension automatique n\'a été déclenchée par cet échec.');
    }
}

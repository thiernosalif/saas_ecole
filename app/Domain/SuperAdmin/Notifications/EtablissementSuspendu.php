<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EtablissementSuspendu extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $nomEcole, private readonly string $motif) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Compte suspendu — {$this->nomEcole}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("L'accès de « {$this->nomEcole} » à la plateforme a été suspendu.")
            ->line("Motif : {$this->motif}")
            ->line('Vos données sont préservées. Contactez notre équipe pour régulariser votre abonnement.');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Notifications;

use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationDirecteur extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Etablissement $etablissement,
        private readonly string $motDePasseTemporaire,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $lienConnexion = sprintf(
            '%s://%s.%s/login',
            parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https',
            $this->etablissement->sous_domaine,
            config('app.tenant_central_domain'),
        );

        return (new MailMessage)
            ->subject("Bienvenue sur la plateforme — {$this->etablissement->nom}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le compte administrateur de « {$this->etablissement->nom} » a été créé sur la plateforme.")
            ->line("Identifiant : {$notifiable->email}")
            ->line("Mot de passe temporaire : {$this->motDePasseTemporaire}")
            ->action('Se connecter', $lienConnexion)
            ->line('Merci de changer ce mot de passe dès votre première connexion.');
    }
}

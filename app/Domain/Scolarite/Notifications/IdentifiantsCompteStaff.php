<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Notifications;

use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IdentifiantsCompteStaff extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Etablissement $etablissement,
        private readonly string $role,
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

        $libelleRole = $this->role === 'PROF' ? 'Professeur' : 'Scolarité';

        return (new MailMessage)
            ->subject("Votre compte sur la plateforme — {$this->etablissement->nom}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un compte {$libelleRole} vous a été créé sur « {$this->etablissement->nom} ».")
            ->line("Identifiant : {$notifiable->email}")
            ->line("Mot de passe temporaire : {$this->motDePasseTemporaire}")
            ->action('Se connecter', $lienConnexion)
            ->line('Merci de changer ce mot de passe dès votre première connexion.');
    }
}

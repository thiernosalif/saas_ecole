<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Console\Commands;

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Services\EcoleService;
use Illuminate\Console\Command;

/**
 * `seedRolesParDefaut()` (EcoleService) n'est appelée qu'à la création d'une
 * école (`creerEcole()`) : une école déjà existante n'obtient jamais un rôle
 * ajouté à `ROLES_PAR_DEFAUT` après coup (ex. COMPTABLE/SECRETAIRE, Session 14).
 * Commande à exécuter manuellement une fois après déploiement d'un nouveau
 * rôle par défaut — idempotente (Role::firstOrCreate), jamais planifiée dans
 * le scheduler contrairement à app:check-abonnements.
 */
class SyncRolesDefautCommand extends Command
{
    protected $signature = 'app:sync-roles-defaut';

    protected $description = 'Crée les rôles par défaut manquants (ROLES_PAR_DEFAUT) sur toutes les écoles existantes.';

    public function __construct(private readonly EcoleService $ecoles)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Etablissement::all()->each(function (Etablissement $etablissement): void {
            $this->ecoles->seedRolesParDefaut($etablissement);
        });

        return self::SUCCESS;
    }
}

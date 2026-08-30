<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Console\Commands;

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Models\ReglementSaas;
use App\Domain\SuperAdmin\Notifications\AvertissementArchivage;
use App\Domain\SuperAdmin\Notifications\RappelAbonnement;
use App\Domain\SuperAdmin\Services\AccesService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Cycle de suspension automatique pour non-paiement (§15.4), planifié
 * quotidiennement à 06h00 (cf. routes/console.php). Deux sources d'échéance
 * gérées séparément : la fin d'essai gratuit (etablissement.date_fin_essai)
 * et les factures SaaS en attente (reglement_saas, échéance = fin du mois
 * facturé).
 */
class CheckAbonnementsCommand extends Command
{
    protected $signature = 'app:check-abonnements';

    protected $description = "Vérifie les échéances d'abonnement et applique le cycle J-7/J-3/J0/J+30/J+60.";

    public function __construct(private readonly AccesService $acces)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $aujourdhui = today();

        $this->traiterEssaisGratuits($aujourdhui);
        $this->traiterFacturesImpayees($aujourdhui);
        $this->traiterEcolesSuspendues($aujourdhui);

        return self::SUCCESS;
    }

    private function traiterEssaisGratuits(Carbon $aujourdhui): void
    {
        Etablissement::where('statut', Etablissement::STATUT_ACTIF)
            ->whereNotNull('date_fin_essai')
            ->get()
            ->each(function (Etablissement $etablissement) use ($aujourdhui) {
                $joursRestants = $aujourdhui->diffInDays($etablissement->date_fin_essai, false);
                $this->appliquerEcheance($etablissement, $joursRestants, "Fin de l'essai gratuit, aucun règlement reçu.");
            });
    }

    private function traiterFacturesImpayees(Carbon $aujourdhui): void
    {
        ReglementSaas::where('statut', ReglementSaas::STATUT_EN_ATTENTE)
            ->with('etablissement')
            ->get()
            ->each(function (ReglementSaas $reglement) use ($aujourdhui) {
                $etablissement = $reglement->etablissement;

                if (! $etablissement || $etablissement->statut !== Etablissement::STATUT_ACTIF) {
                    return;
                }

                $echeance = Carbon::createFromFormat('Y-m', $reglement->mois)->endOfMonth();
                $joursRestants = $aujourdhui->diffInDays($echeance, false);
                $this->appliquerEcheance($etablissement, $joursRestants, "Facture {$reglement->mois} impayée.");
            });
    }

    private function appliquerEcheance(Etablissement $etablissement, int $joursRestants, string $motifSuspension): void
    {
        match (true) {
            in_array($joursRestants, [7, 3], true) => $this->acces->notifierDirecteurs(
                $etablissement,
                new RappelAbonnement($etablissement->nom, $joursRestants),
            ),
            $joursRestants <= 0 => $this->acces->suspendre($etablissement, $motifSuspension),
            default => null,
        };
    }

    private function traiterEcolesSuspendues(Carbon $aujourdhui): void
    {
        Etablissement::where('statut', Etablissement::STATUT_SUSPENDU)
            ->whereNotNull('date_suspension')
            ->get()
            ->each(function (Etablissement $etablissement) use ($aujourdhui) {
                $joursDepuisSuspension = $etablissement->date_suspension->diffInDays($aujourdhui);

                match (true) {
                    $joursDepuisSuspension >= 60 => $this->acces->archiver($etablissement),
                    $joursDepuisSuspension === 30 => $this->acces->notifierDirecteurs(
                        $etablissement,
                        new AvertissementArchivage($etablissement->nom),
                    ),
                    default => null,
                };
            });
    }
}

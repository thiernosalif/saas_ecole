<?php

declare(strict_types=1);

namespace App\Domain\Notes\Services;

use App\Domain\Notes\Models\Bulletin;
use App\Domain\Scolarite\Models\Absence;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Models\Trimestre;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BulletinService
{
    private const DISQUE = 'local';

    public function __construct(private readonly MoyenneService $moyennes) {}

    /**
     * Génère (ou régénère) le bulletin de chaque élève classé d'une classe pour
     * un trimestre donné, PDF inclus.
     *
     * @return Collection<int, Bulletin>
     */
    public function genererPourClasse(string $classeId, string $trimestreId): Collection
    {
        return $this->moyennes->classement($classeId, $trimestreId)
            ->map(fn (array $ligne) => $this->genererPourEleve(
                $ligne['eleve_id'],
                $trimestreId,
                moyenneGenerale: $ligne['moyenne'],
                rang: $ligne['rang'],
                effectif: $ligne['effectif'],
            ));
    }

    public function genererPourEleve(
        string $eleveId,
        string $trimestreId,
        ?float $moyenneGenerale = null,
        ?int $rang = null,
        ?int $effectif = null,
    ): Bulletin {
        $eleve = Personne::findOrFail($eleveId);
        $trimestre = Trimestre::findOrFail($trimestreId);
        $moyenneGenerale ??= $this->moyennes->moyenneGenerale($eleveId, $trimestreId);

        $inscription = Inscription::where('eleve_id', $eleveId)
            ->where('annee_id', $trimestre->annee_id)
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->first();

        $lignesMatieres = $inscription
            ? $this->moyennes->moyennesParMatiere($eleveId, $inscription->classe_id, $trimestre->annee_id, $trimestreId)
            : collect();

        [$absencesJustifiees, $absencesNonJustifiees] = $this->compterAbsences($eleveId, $trimestre);

        $bulletin = DB::transaction(fn () => Bulletin::updateOrCreate(
            ['eleve_id' => $eleveId, 'trimestre_id' => $trimestreId],
            [
                'moyenne_generale' => $moyenneGenerale,
                'rang' => $rang,
                'effectif_classe' => $effectif,
                'mention' => $this->mention($moyenneGenerale),
                'nb_absences_justifiees' => $absencesJustifiees,
                'nb_absences_non_justifiees' => $absencesNonJustifiees,
            ],
        ));

        $chemin = $this->genererPdf($bulletin, $eleve, $trimestre, $inscription, $lignesMatieres);

        $bulletin->update([
            'url_pdf' => $chemin,
            'valide' => true,
            'genere_at' => now(),
        ]);

        return $bulletin->refresh();
    }

    /**
     * @param  Collection<int, array{matiere: \App\Domain\Notes\Models\Matiere, moyenne: ?float}>  $lignesMatieres
     */
    private function genererPdf(
        Bulletin $bulletin,
        Personne $eleve,
        Trimestre $trimestre,
        ?Inscription $inscription,
        Collection $lignesMatieres,
    ): string {
        $pdf = Pdf::loadView('pdf.bulletin', [
            'etablissement' => app()->bound('currentTenant') ? app('currentTenant') : null,
            'eleve' => $eleve,
            'classe' => $inscription?->classe,
            'trimestre' => $trimestre,
            'bulletin' => $bulletin,
            'lignesMatieres' => $lignesMatieres,
        ])->setPaper('a4');

        $chemin = "bulletins/{$bulletin->id}.pdf";

        Storage::disk(self::DISQUE)->put($chemin, $pdf->output());

        return $chemin;
    }

    /**
     * @return array{0: int, 1: int} [justifiées, non justifiées]
     */
    private function compterAbsences(string $eleveId, Trimestre $trimestre): array
    {
        $absences = Absence::where('eleve_id', $eleveId)
            ->where('type', Absence::TYPE_ABSENCE)
            ->whereBetween('date', [$trimestre->date_debut, $trimestre->date_fin])
            ->get(['justifiee']);

        return [
            $absences->where('justifiee', true)->count(),
            $absences->where('justifiee', false)->count(),
        ];
    }

    private function mention(?float $moyenne): ?string
    {
        return match (true) {
            $moyenne === null => null,
            $moyenne >= 16 => 'Félicitations',
            $moyenne >= 14 => 'Encouragements',
            $moyenne >= 12 => "Tableau d'honneur",
            $moyenne >= 10 => 'Passable',
            default => 'Insuffisant',
        };
    }
}

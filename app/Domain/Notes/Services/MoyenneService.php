<?php

declare(strict_types=1);

namespace App\Domain\Notes\Services;

use App\Domain\Notes\Models\AffectationProf;
use App\Domain\Notes\Models\Matiere;
use App\Domain\Notes\Models\Note;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Models\Trimestre;
use Illuminate\Support\Collection;

/**
 * Calcule les moyennes pondérées et le classement d'une classe.
 *
 * Deux niveaux de pondération distincts (cf. PROJET_LARAVEL.md §5.3) :
 * - au sein d'une matière, chaque note a son propre coefficient (devoir vs
 *   composition) ;
 * - au sein du bulletin, chaque matière a son propre coefficient (§4.2).
 */
class MoyenneService
{
    public function moyenneMatiere(string $eleveId, string $matiereId, string $trimestreId): ?float
    {
        $notes = Note::where('eleve_id', $eleveId)
            ->where('matiere_id', $matiereId)
            ->where('trimestre_id', $trimestreId)
            ->get(['valeur', 'coefficient']);

        $sommeCoefficients = (float) $notes->sum('coefficient');

        if ($notes->isEmpty() || $sommeCoefficients <= 0) {
            return null;
        }

        $sommePonderee = $notes->sum(fn (Note $note) => (float) $note->valeur * (float) $note->coefficient);

        return round($sommePonderee / $sommeCoefficients, 2);
    }

    /**
     * Moyenne par matière enseignée dans la classe de l'élève (via affectation_prof),
     * pas sur l'ensemble des matières de l'établissement.
     *
     * @return Collection<int, array{matiere: Matiere, moyenne: ?float}>
     */
    public function moyennesParMatiere(string $eleveId, string $classeId, string $anneeId, string $trimestreId): Collection
    {
        $matieres = AffectationProf::where('classe_id', $classeId)
            ->where('annee_id', $anneeId)
            ->with('matiere')
            ->get()
            ->pluck('matiere')
            ->filter()
            ->unique('id')
            ->values();

        return $matieres->map(fn (Matiere $matiere) => [
            'matiere' => $matiere,
            'moyenne' => $this->moyenneMatiere($eleveId, $matiere->id, $trimestreId),
        ]);
    }

    public function moyenneGenerale(string $eleveId, string $trimestreId): ?float
    {
        $lignes = $this->lignesMatieresNotees($eleveId, $trimestreId);

        if (! $lignes) {
            return null;
        }

        return $this->calculerMoyennePonderee($lignes);
    }

    /**
     * Classement décroissant des élèves inscrits (actifs) dans une classe pour
     * un trimestre donné. Seuls les élèves ayant au moins une moyenne calculable
     * entrent dans le classement.
     *
     * @return Collection<int, array{eleve_id: string, moyenne: float, rang: int, effectif: int}>
     */
    public function classement(string $classeId, string $trimestreId): Collection
    {
        $trimestre = Trimestre::findOrFail($trimestreId);

        $eleveIds = Inscription::where('classe_id', $classeId)
            ->where('annee_id', $trimestre->annee_id)
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->pluck('eleve_id');

        $moyennes = $eleveIds
            ->map(fn (string $eleveId) => [
                'eleve_id' => $eleveId,
                'moyenne' => $this->moyenneGenerale($eleveId, $trimestreId),
            ])
            ->filter(fn (array $ligne) => $ligne['moyenne'] !== null)
            ->sortByDesc('moyenne')
            ->values();

        $effectif = $moyennes->count();

        return $moyennes->map(fn (array $ligne, int $index) => [
            ...$ligne,
            'rang' => $index + 1,
            'effectif' => $effectif,
        ]);
    }

    /**
     * @return Collection<int, array{matiere: Matiere, moyenne: ?float}>|null
     */
    private function lignesMatieresNotees(string $eleveId, string $trimestreId): ?Collection
    {
        $trimestre = Trimestre::findOrFail($trimestreId);

        $inscription = Inscription::where('eleve_id', $eleveId)
            ->where('annee_id', $trimestre->annee_id)
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->first();

        if (! $inscription) {
            return null;
        }

        $lignes = $this->moyennesParMatiere($eleveId, $inscription->classe_id, $trimestre->annee_id, $trimestreId)
            ->filter(fn (array $ligne) => $ligne['moyenne'] !== null)
            ->values();

        return $lignes->isEmpty() ? null : $lignes;
    }

    /**
     * @param  Collection<int, array{matiere: Matiere, moyenne: ?float}>  $lignes
     */
    private function calculerMoyennePonderee(Collection $lignes): ?float
    {
        $sommeCoefficients = (float) $lignes->sum(fn (array $ligne) => (float) $ligne['matiere']->coefficient);

        if ($sommeCoefficients <= 0) {
            return null;
        }

        $sommePonderee = $lignes->sum(fn (array $ligne) => $ligne['moyenne'] * (float) $ligne['matiere']->coefficient);

        return round($sommePonderee / $sommeCoefficients, 2);
    }
}

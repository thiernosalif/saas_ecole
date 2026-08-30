<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Services;

use App\Domain\SuperAdmin\Models\Etablissement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Agrégations business (§15.9) : MRR/ARR à partir du plan actif de chaque
 * école, churn/LTV calculés sur date_resiliation.
 */
class AnalyticsService
{
    public function mrr(): float
    {
        return (float) Etablissement::query()
            ->where('etablissement.statut', Etablissement::STATUT_ACTIF)
            ->join('plan_tarifaire', 'plan_tarifaire.id', '=', 'etablissement.plan_id')
            ->sum('plan_tarifaire.prix_mensuel');
    }

    public function arr(): float
    {
        return $this->mrr() * 12;
    }

    public function arpu(): float
    {
        $nbActifs = Etablissement::where('statut', Etablissement::STATUT_ACTIF)->count();

        return $nbActifs > 0 ? round($this->mrr() / $nbActifs, 2) : 0.0;
    }

    public function tauxChurn(?Carbon $mois = null): float
    {
        $mois ??= now();
        $debutMois = $mois->copy()->startOfMonth();
        $finMois = $mois->copy()->endOfMonth();

        $actifsDebutMois = Etablissement::where('created_at', '<', $debutMois)
            ->where(fn ($query) => $query->whereNull('date_resiliation')->orWhere('date_resiliation', '>=', $debutMois))
            ->count();

        if ($actifsDebutMois === 0) {
            return 0.0;
        }

        $resiliesDansLeMois = Etablissement::whereBetween('date_resiliation', [$debutMois, $finMois])->count();

        return round(($resiliesDansLeMois / $actifsDebutMois) * 100, 2);
    }

    public function ltv(): float
    {
        $churn = $this->tauxChurn();

        return $churn > 0 ? round($this->arpu() / ($churn / 100), 2) : 0.0;
    }

    /**
     * @return Collection<int, array{mois: string, nouvelles_ecoles: int}>
     */
    public function croissance(int $nbMois = 6): Collection
    {
        return collect(range(0, $nbMois - 1))
            ->map(fn (int $i) => now()->subMonths($i)->startOfMonth())
            ->reverse()
            ->values()
            ->map(fn (Carbon $debut) => [
                'mois' => $debut->format('Y-m'),
                'nouvelles_ecoles' => Etablissement::whereBetween(
                    'created_at',
                    [$debut, $debut->copy()->endOfMonth()],
                )->count(),
            ]);
    }

    public function resume(): array
    {
        return [
            'mrr' => $this->mrr(),
            'arr' => $this->arr(),
            'arpu' => $this->arpu(),
            'churn' => $this->tauxChurn(),
            'ltv' => $this->ltv(),
            'nb_ecoles_actives' => Etablissement::where('statut', Etablissement::STATUT_ACTIF)->count(),
            'nb_ecoles_suspendues' => Etablissement::where('statut', Etablissement::STATUT_SUSPENDU)->count(),
            'croissance' => $this->croissance(),
        ];
    }
}

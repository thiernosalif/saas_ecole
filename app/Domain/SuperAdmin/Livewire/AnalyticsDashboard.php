<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Livewire;

use App\Domain\SuperAdmin\Services\AnalyticsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Analytics')]
class AnalyticsDashboard extends Component
{
    public array $stats = [];

    public function mount(AnalyticsService $analytics): void
    {
        $this->stats = $this->charger($analytics);
    }

    public function actualiser(AnalyticsService $analytics): void
    {
        $this->stats = $this->charger($analytics);

        // Le graphique Chart.js vit dans un conteneur wire:ignore (cf. la vue) pour
        // ne pas être détruit à chaque re-render Livewire : on lui pousse les
        // nouvelles données via un évènement navigateur plutôt que par le DOM diffing.
        $this->dispatch('analytics-refreshed', croissance: $this->stats['croissance']);
    }

    /**
     * @return array<string, mixed>
     */
    private function charger(AnalyticsService $analytics): array
    {
        $stats = $analytics->resume();
        $stats['croissance'] = $stats['croissance']->toArray();

        return $stats;
    }

    public function render(): View
    {
        return view('livewire.admin.analytics-dashboard');
    }
}

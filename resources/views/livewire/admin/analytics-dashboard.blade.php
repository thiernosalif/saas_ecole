<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-800">Analytics</h1>

        <flux:button wire:click="actualiser" variant="ghost" size="sm">Actualiser</flux:button>
    </div>

    <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-zinc-500">MRR</p>
            <p class="mt-1 text-xl font-semibold text-zinc-800">{{ number_format($stats['mrr'], 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-zinc-500">ARR</p>
            <p class="mt-1 text-xl font-semibold text-zinc-800">{{ number_format($stats['arr'], 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-zinc-500">ARPU</p>
            <p class="mt-1 text-xl font-semibold text-zinc-800">{{ number_format($stats['arpu'], 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Churn</p>
            <p class="mt-1 text-xl font-semibold text-zinc-800">{{ $stats['churn'] }}%</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-zinc-500">LTV</p>
            <p class="mt-1 text-xl font-semibold text-zinc-800">{{ number_format($stats['ltv'], 0, ',', ' ') }} FCFA</p>
        </div>
    </div>

    <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Écoles actives</p>
            <p class="mt-1 text-xl font-semibold text-green-700">{{ $stats['nb_ecoles_actives'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Écoles suspendues</p>
            <p class="mt-1 text-xl font-semibold text-amber-700">{{ $stats['nb_ecoles_suspendues'] }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4">
        <p class="mb-3 text-sm font-semibold text-zinc-800">Nouvelles écoles par mois</p>

        {{-- wire:ignore : le canvas Chart.js est initialisé une fois par Alpine et ne doit
             jamais être touché par le DOM diffing de Livewire ; les rafraîchissements
             passent par l'évènement 'analytics-refreshed' dispatché depuis le composant. --}}
        <div wire:ignore x-data="analyticsChart(@js($stats['croissance']))" x-on:analytics-refreshed.window="update($event.detail.croissance)">
            <canvas x-ref="canvas" height="80"></canvas>
        </div>
    </div>
</div>

<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-800">Écoles</h1>

        <flux:button :href="route('admin.portail.onboarding')" variant="filled">
            Ajouter une école
        </flux:button>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Rechercher par nom, sous-domaine ou ville…"
            class="w-full max-w-sm rounded-lg border border-zinc-200 px-3 py-2 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-zinc-800/20"
        />

        <select
            wire:model.live="statutFiltre"
            class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-zinc-800/20"
        >
            <option value="">Tous les statuts</option>
            <option value="ACTIF">Actif</option>
            <option value="SUSPENDU">Suspendu</option>
            <option value="ARCHIVE">Archivé</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-4 py-3">École</th>
                    <th class="px-4 py-3">Sous-domaine</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($etablissements as $etablissement)
                    <tr wire:key="etablissement-{{ $etablissement->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-800">{{ $etablissement->nom }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $etablissement->sous_domaine }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $etablissement->plan?->nom ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-1 text-xs font-medium',
                                'bg-green-50 text-green-700' => $etablissement->statut === 'ACTIF',
                                'bg-amber-50 text-amber-700' => $etablissement->statut === 'SUSPENDU',
                                'bg-zinc-100 text-zinc-500' => $etablissement->statut === 'ARCHIVE',
                            ])>
                                {{ $etablissement->statut }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <flux:button :href="route('admin.portail.ecoles.detail', $etablissement)" variant="ghost" size="sm">
                                Détails
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-400">Aucune école trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $etablissements->links() }}
    </div>
</div>

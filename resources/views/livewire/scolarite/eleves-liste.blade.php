<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-800">Élèves</h1>

        @can('create', \App\Domain\Scolarite\Models\Personne::class)
            <flux:button :href="route('scolarite.eleves.create')" variant="filled">
                Ajouter un élève
            </flux:button>
        @endcan
    </div>

    <div class="mb-4">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Rechercher par nom, prénom ou matricule…"
            class="w-full max-w-sm rounded-lg border border-zinc-200 px-3 py-2 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-zinc-800/20"
        />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Matricule</th>
                    <th class="px-4 py-3">Classe</th>
                    <th class="px-4 py-3">Genre</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($eleves as $eleve)
                    <tr wire:key="eleve-{{ $eleve->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-800">{{ $eleve->nom }} {{ $eleve->prenom }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $eleve->matricule ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $eleve->inscriptionActive()?->classe?->libelle ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $eleve->genre ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @can('update', $eleve)
                                    <flux:button :href="route('scolarite.eleves.edit', $eleve)" variant="ghost" size="sm">
                                        Modifier
                                    </flux:button>
                                @endcan

                                @can('delete', $eleve)
                                    <flux:button wire:click="confirmDelete('{{ $eleve->id }}')" variant="ghost" size="sm" class="text-red-600">
                                        Supprimer
                                    </flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-400">Aucun élève trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $eleves->links() }}
    </div>

    <x-ui.modal name="confirmingDeleteId" title="Supprimer cet élève ?">
        <p class="text-sm text-zinc-600">Cette action est irréversible.</p>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="$set('confirmingDeleteId', null)" variant="ghost">Annuler</flux:button>
            <flux:button wire:click="delete" variant="danger">Supprimer</flux:button>
        </div>
    </x-ui.modal>
</div>

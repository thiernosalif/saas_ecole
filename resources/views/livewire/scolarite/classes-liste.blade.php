<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-800">Classes</h1>

        @can('create', \App\Domain\Scolarite\Models\Classe::class)
            <flux:button wire:click="openCreate" variant="filled">Ajouter une classe</flux:button>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Libellé</th>
                    <th class="px-4 py-3">Niveau</th>
                    <th class="px-4 py-3">Année scolaire</th>
                    <th class="px-4 py-3">Professeur principal</th>
                    <th class="px-4 py-3">Capacité</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($classes as $classe)
                    <tr wire:key="classe-{{ $classe->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-800">{{ $classe->libelle }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $classe->niveau?->libelle ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $classe->anneeScolaire?->libelle ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">
                            {{ $classe->profPrincipal ? $classe->profPrincipal->nom.' '.$classe->profPrincipal->prenom : '—' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600">{{ $classe->capacite_max ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @can('update', $classe)
                                    <flux:button wire:click="openEdit('{{ $classe->id }}')" variant="ghost" size="sm">
                                        Modifier
                                    </flux:button>
                                @endcan

                                @can('delete', $classe)
                                    <flux:button wire:click="confirmDelete('{{ $classe->id }}')" variant="ghost" size="sm" class="text-red-600">
                                        Supprimer
                                    </flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-400">Aucune classe trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $classes->links() }}
    </div>

    <x-ui.modal name="showForm" :title="$editingId ? 'Modifier la classe' : 'Ajouter une classe'">
        <form wire:submit="save" class="space-y-4">
            <x-ui.input name="libelle" label="Libellé" wire:model="libelle" required />

            <x-ui.select name="niveau_id" label="Niveau" wire:model="niveau_id" placeholder="—"
                :options="$niveaux->pluck('libelle', 'id')" />

            <x-ui.select name="annee_id" label="Année scolaire" wire:model="annee_id" placeholder="—"
                :options="$annees->pluck('libelle', 'id')" />

            <x-ui.input name="capacite_max" type="number" label="Capacité maximale" wire:model="capacite_max" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button wire:click="$set('showForm', false)" variant="ghost" type="button">Annuler</flux:button>
                <flux:button type="submit" variant="filled">Enregistrer</flux:button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal name="confirmingDeleteId" title="Supprimer cette classe ?">
        <p class="text-sm text-zinc-600">Cette action est irréversible.</p>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="$set('confirmingDeleteId', null)" variant="ghost">Annuler</flux:button>
            <flux:button wire:click="delete" variant="danger">Supprimer</flux:button>
        </div>
    </x-ui.modal>
</div>

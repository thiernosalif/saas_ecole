<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-800">Devoirs</h1>

        @can('create', \App\Domain\Planning\Models\Devoir::class)
            <flux:button wire:click="openCreate" variant="filled">Ajouter un devoir</flux:button>
        @endcan
    </div>

    <div class="mb-6 max-w-xs">
        <x-ui.select name="classeIdFiltre" label="Classe" wire:model.live="classeIdFiltre" placeholder="Toutes les classes"
            :options="$classes->pluck('libelle', 'id')" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Classe</th>
                    <th class="px-4 py-3">Matière</th>
                    <th class="px-4 py-3">Titre</th>
                    <th class="px-4 py-3">Date de remise</th>
                    <th class="px-4 py-3">Professeur</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($devoirs as $devoir)
                    <tr wire:key="devoir-{{ $devoir->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-800">{{ $devoir->classe?->libelle }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $devoir->matiere?->libelle }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $devoir->titre }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $devoir->date_remise->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-zinc-600">
                            {{ $devoir->prof ? $devoir->prof->nom.' '.$devoir->prof->prenom : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @can('update', $devoir)
                                    <flux:button wire:click="openEdit('{{ $devoir->id }}')" variant="ghost" size="sm">
                                        Modifier
                                    </flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-400">Aucun devoir trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $devoirs->links() }}
    </div>

    <x-ui.modal name="showForm" :title="$editingId ? 'Modifier le devoir' : 'Ajouter un devoir'">
        <form wire:submit="save" class="space-y-4">
            @unless ($editingId)
                <x-ui.select name="classe_id" label="Classe" wire:model.live="classe_id" placeholder="Choisir une classe"
                    :options="$classes->pluck('libelle', 'id')" />

                <x-ui.select name="matiere_id" label="Matière" wire:model="matiere_id" placeholder="Choisir une matière"
                    :options="$matieres->pluck('libelle', 'id')" />
            @else
                <p class="text-sm text-zinc-500">
                    Classe : {{ $classes->firstWhere('id', $classe_id)?->libelle }}
                    — Matière : {{ $matieres->firstWhere('id', $matiere_id)?->libelle }}
                </p>
            @endunless

            <x-ui.input name="titre" label="Titre" wire:model="titre" required />

            <div>
                <label for="description" class="mb-1 block text-sm font-medium text-zinc-700">Description</label>
                <textarea id="description" wire:model="description" rows="3"
                    class="block w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-zinc-800/20"></textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.input name="date_remise" type="date" label="Date de remise" wire:model="date_remise" required />

            <x-ui.select name="prof_id" label="Professeur" wire:model="prof_id" placeholder="—"
                :options="$profs->mapWithKeys(fn ($p) => [$p->id => $p->nom.' '.$p->prenom])" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button wire:click="$set('showForm', false)" variant="ghost" type="button">Annuler</flux:button>
                <flux:button type="submit" variant="filled">Enregistrer</flux:button>
            </div>
        </form>
    </x-ui.modal>
</div>

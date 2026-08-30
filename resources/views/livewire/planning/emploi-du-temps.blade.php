<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-800">Emploi du temps</h1>

        @if ($classe_id)
            @can('create', \App\Domain\Planning\Models\Seance::class)
                <flux:button wire:click="openCreate" variant="filled">Ajouter une séance</flux:button>
            @endcan
        @endif
    </div>

    <div class="mb-6 max-w-xs">
        <x-ui.select name="classe_id" label="Classe" wire:model.live="classe_id" placeholder="Choisir une classe"
            :options="$classes->pluck('libelle', 'id')" />
    </div>

    @if (! $classe_id)
        <p class="text-sm text-zinc-400">Choisissez une classe pour afficher son emploi du temps.</p>
    @else
        <div class="grid grid-cols-1 gap-4 overflow-x-auto md:grid-cols-6">
            @foreach ($jours as $numero => $libelle)
                <div class="min-w-[9rem] rounded-xl border border-zinc-200 bg-white">
                    <div class="border-b border-zinc-200 bg-zinc-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        {{ $libelle }}
                    </div>

                    <div class="space-y-2 p-2">
                        @forelse ($seancesParJour->get($numero, collect()) as $seance)
                            <div wire:key="seance-{{ $seance->id }}"
                                 class="rounded-lg border border-zinc-200 px-2 py-2 text-xs">
                                <p class="font-medium text-zinc-800">
                                    {{ substr($seance->heure_debut, 0, 5) }} – {{ substr($seance->heure_fin, 0, 5) }}
                                </p>
                                <p class="text-zinc-600">{{ $seance->matiere?->libelle }}</p>
                                <p class="text-zinc-400">{{ $seance->prof ? $seance->prof->nom.' '.$seance->prof->prenom : '—' }}</p>
                                <p class="text-zinc-400">{{ $seance->salle?->nom ?? '—' }}</p>

                                @can('update', $seance)
                                    <button type="button" wire:click="openEdit('{{ $seance->id }}')"
                                            class="mt-1 text-xs font-medium text-zinc-500 hover:text-zinc-800">
                                        Modifier
                                    </button>
                                @endcan
                            </div>
                        @empty
                            <p class="px-1 py-2 text-xs text-zinc-300">—</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-ui.modal name="showForm" :title="$editingId ? 'Modifier la séance' : 'Ajouter une séance'">
        <form wire:submit="save" class="space-y-4">
            @unless ($editingId)
                <x-ui.select name="matiere_id" label="Matière" wire:model="matiere_id" placeholder="Choisir une matière"
                    :options="$matieres->pluck('libelle', 'id')" />
            @else
                <p class="text-sm text-zinc-500">Matière : {{ $matieres->firstWhere('id', $matiere_id)?->libelle }}</p>
            @endunless

            <x-ui.select name="jour_semaine" label="Jour" wire:model="jour_semaine" placeholder="Choisir un jour"
                :options="$jours" />

            <div class="grid grid-cols-2 gap-4">
                <x-ui.input name="heure_debut" type="time" label="Heure de début" wire:model="heure_debut" required />
                <x-ui.input name="heure_fin" type="time" label="Heure de fin" wire:model="heure_fin" required />
            </div>

            <x-ui.select name="salle_id" label="Salle" wire:model="salle_id" placeholder="—"
                :options="$salles->pluck('nom', 'id')" />

            <x-ui.select name="prof_id" label="Professeur" wire:model="prof_id" placeholder="—"
                :options="$profs->mapWithKeys(fn ($p) => [$p->id => $p->nom.' '.$p->prenom])" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button wire:click="$set('showForm', false)" variant="ghost" type="button">Annuler</flux:button>
                <flux:button type="submit" variant="filled">Enregistrer</flux:button>
            </div>
        </form>
    </x-ui.modal>
</div>

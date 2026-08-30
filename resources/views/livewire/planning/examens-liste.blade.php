<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-800">Examens</h1>

        @can('create', \App\Domain\Planning\Models\Examen::class)
            <flux:button wire:click="openCreate" variant="filled">Ajouter un examen</flux:button>
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
                    <th class="px-4 py-3">Libellé</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Salle</th>
                    <th class="px-4 py-3">Barème</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($examens as $examen)
                    <tr wire:key="examen-{{ $examen->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-800">{{ $examen->classe?->libelle }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $examen->matiere?->libelle }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $examen->libelle ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">
                            {{ $examen->date_examen->format('d/m/Y') }}
                            @if ($examen->heure_debut)
                                — {{ substr($examen->heure_debut, 0, 5) }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600">{{ $examen->salle?->nom ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $examen->bareme }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @can('update', $examen)
                                    <flux:button wire:click="openEdit('{{ $examen->id }}')" variant="ghost" size="sm">
                                        Modifier
                                    </flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-400">Aucun examen trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $examens->links() }}
    </div>

    <x-ui.modal name="showForm" :title="$editingId ? 'Modifier l’examen' : 'Ajouter un examen'">
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

            <x-ui.input name="libelle" label="Libellé" wire:model="libelle" />

            <x-ui.select name="trimestre_id" label="Trimestre" wire:model="trimestre_id" placeholder="—"
                :options="$trimestres->mapWithKeys(fn ($t) => [$t->id => 'Trimestre '.$t->numero])" />

            <div class="grid grid-cols-2 gap-4">
                <x-ui.input name="date_examen" type="date" label="Date" wire:model="date_examen" required />
                <x-ui.input name="heure_debut" type="time" label="Heure de début" wire:model="heure_debut" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-ui.input name="duree_minutes" type="number" label="Durée (minutes)" wire:model="duree_minutes" />
                <x-ui.input name="bareme" type="number" step="0.5" label="Barème" wire:model="bareme" />
            </div>

            <x-ui.select name="salle_id" label="Salle" wire:model="salle_id" placeholder="—"
                :options="$salles->pluck('nom', 'id')" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button wire:click="$set('showForm', false)" variant="ghost" type="button">Annuler</flux:button>
                <flux:button type="submit" variant="filled">Enregistrer</flux:button>
            </div>
        </form>
    </x-ui.modal>
</div>

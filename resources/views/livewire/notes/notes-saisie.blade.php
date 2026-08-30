<div class="max-w-4xl">
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <h1 class="mb-6 text-xl font-semibold text-zinc-800">Saisie des notes</h1>

    @if (! $annee)
        <p class="text-sm text-zinc-400">Aucune année scolaire active.</p>
    @else
        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
            <x-ui.select name="classe_id" label="Classe" wire:model.live="classe_id" placeholder="Choisir une classe"
                :options="$classes->pluck('libelle', 'id')" />

            <x-ui.select name="matiere_id" label="Matière" wire:model.live="matiere_id" placeholder="Choisir une matière"
                :options="$matieres->pluck('libelle', 'id')" />

            <x-ui.select name="trimestre_id" label="Trimestre" wire:model.live="trimestre_id" placeholder="Choisir un trimestre"
                :options="$trimestres->mapWithKeys(fn ($t) => [$t->id => 'Trimestre '.$t->numero])" />

            <x-ui.select name="type" label="Type" wire:model.live="type"
                :options="['DEVOIR' => 'Devoir', 'COMPOSITION' => 'Composition']" />
        </div>

        @if ($classe_id && $matiere_id && $trimestre_id && $eleves->isEmpty())
            <p class="text-sm text-zinc-400">Aucun élève inscrit dans cette classe, ou couple classe/matière non autorisé.</p>
        @endif

        @if ($eleves->isNotEmpty())
            <form wire:submit="save">
                <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Élève</th>
                                <th class="px-4 py-3">Note / 20</th>
                                <th class="px-4 py-3">Appréciation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($eleves as $eleveId => $eleve)
                                <tr wire:key="ligne-{{ $eleveId }}">
                                    <td class="px-4 py-3 font-medium text-zinc-800">{{ $eleve->nom }} {{ $eleve->prenom }}</td>
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.25" min="0" max="20"
                                            wire:model="lignes.{{ $eleveId }}.valeur"
                                            class="w-24 rounded-lg border border-zinc-200 px-2 py-1 text-sm" />
                                        @error('lignes.'.$eleveId.'.valeur')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" wire:model="lignes.{{ $eleveId }}.appreciation"
                                            class="w-full rounded-lg border border-zinc-200 px-2 py-1 text-sm" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <flux:button type="submit" variant="filled">Enregistrer les notes</flux:button>
                </div>
            </form>
        @endif
    @endif
</div>

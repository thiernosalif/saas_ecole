<div class="max-w-3xl">
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <h1 class="mb-6 text-xl font-semibold text-zinc-800">Saisie des absences</h1>

    <div class="mb-6 grid grid-cols-2 gap-4">
        <x-ui.select name="classe_id" label="Classe" wire:model.live="classe_id" placeholder="Choisir une classe"
            :options="$classes->pluck('libelle', 'id')" />

        <x-ui.input name="date" type="date" label="Date" wire:model.live="date" />
    </div>

    @if ($classe_id && $eleves->isEmpty())
        <p class="text-sm text-zinc-400">Aucun élève inscrit dans cette classe.</p>
    @endif

    @if ($eleves->isNotEmpty())
        <form wire:submit="save">
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-4 py-3">Élève</th>
                            <th class="px-4 py-3">Absent / Retard</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Justifié</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($eleves as $eleveId => $eleve)
                            <tr wire:key="ligne-{{ $eleveId }}">
                                <td class="px-4 py-3 font-medium text-zinc-800">{{ $eleve->nom }} {{ $eleve->prenom }}</td>
                                <td class="px-4 py-3">
                                    <input type="checkbox" wire:model.live="lignes.{{ $eleveId }}.marque"
                                        class="size-4 rounded border-zinc-300 text-zinc-800 focus:ring-zinc-800/20" />
                                </td>
                                <td class="px-4 py-3">
                                    <select wire:model="lignes.{{ $eleveId }}.type"
                                        class="rounded-lg border border-zinc-200 px-2 py-1 text-sm">
                                        <option value="ABSENCE">Absence</option>
                                        <option value="RETARD">Retard</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="checkbox" wire:model="lignes.{{ $eleveId }}.justifiee"
                                        class="size-4 rounded border-zinc-300 text-zinc-800 focus:ring-zinc-800/20" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <flux:button type="submit" variant="filled">Enregistrer les absences</flux:button>
            </div>
        </form>
    @endif
</div>

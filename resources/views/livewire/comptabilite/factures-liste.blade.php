<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-800">Factures — {{ $eleve->nom }} {{ $eleve->prenom }}</h1>

        @can('create', \App\Domain\Comptabilite\Models\Facture::class)
            <flux:button wire:click="openCreate" variant="filled">Ajouter une facture</flux:button>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Numéro</th>
                    <th class="px-4 py-3">Montant total</th>
                    <th class="px-4 py-3">Payé</th>
                    <th class="px-4 py-3">Solde</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Échéance</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($factures as $facture)
                    <tr wire:key="facture-{{ $facture->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-800">{{ $facture->numero ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ number_format((float) $facture->montant_total, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ number_format((float) $facture->montant_paye, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ number_format((float) $facture->soldeRestant(), 0, ',', ' ') }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-50 text-green-700' => $facture->statut === \App\Domain\Comptabilite\Models\Facture::STATUT_PAYEE,
                                'bg-amber-50 text-amber-700' => $facture->statut === \App\Domain\Comptabilite\Models\Facture::STATUT_PARTIELLE,
                                'bg-zinc-100 text-zinc-600' => $facture->statut === \App\Domain\Comptabilite\Models\Facture::STATUT_EN_ATTENTE,
                            ])>
                                {{ $facture->statut }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-zinc-600">{{ $facture->due_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @can('view', $facture)
                                    <flux:button :href="route('comptabilite.reglements.index', $facture)" variant="ghost" size="sm">
                                        Règlements
                                    </flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-400">Aucune facture trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.modal name="showForm" title="Ajouter une facture">
        <form wire:submit="save" class="space-y-4">
            <x-ui.select name="annee_id" label="Année scolaire" wire:model="annee_id" placeholder="—"
                :options="$annees->pluck('libelle', 'id')" />

            <x-ui.input name="numero" label="Numéro (optionnel)" wire:model="numero" />

            <x-ui.input name="montant_total" type="number" step="0.01" min="0.01" label="Montant total" wire:model="montant_total" required />

            <x-ui.input name="due_at" type="date" label="Échéance (optionnel)" wire:model="due_at" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button wire:click="$set('showForm', false)" variant="ghost" type="button">Annuler</flux:button>
                <flux:button type="submit" variant="filled">Enregistrer</flux:button>
            </div>
        </form>
    </x-ui.modal>
</div>

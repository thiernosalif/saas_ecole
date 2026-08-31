<div class="max-w-2xl">
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-zinc-800">Facture {{ $facture->numero ?? $facture->id }}</h1>
        <p class="mt-1 text-sm text-zinc-500">
            {{ $facture->eleve->nom }} {{ $facture->eleve->prenom }}
            — Total {{ number_format((float) $facture->montant_total, 0, ',', ' ') }}
            · Payé {{ number_format((float) $facture->montant_paye, 0, ',', ' ') }}
            · Solde {{ number_format((float) $facture->soldeRestant(), 0, ',', ' ') }}
        </p>
    </div>

    @can('create', \App\Domain\Comptabilite\Models\Reglement::class)
        <form wire:submit="save" class="mb-8 space-y-4 rounded-xl border border-zinc-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-zinc-700">Enregistrer un règlement</h2>

            <div class="grid grid-cols-2 gap-4">
                <x-ui.input name="montant" type="number" step="0.01" min="0.01" label="Montant" wire:model="montant" required />

                <x-ui.select name="moyen_paiement" label="Moyen de paiement" wire:model="moyen_paiement"
                    :options="collect($moyensManuels)->mapWithKeys(fn ($m) => [$m => $m])" />

                <x-ui.input name="reference" label="Référence (optionnel)" wire:model="reference" />

                <x-ui.input name="paid_at" type="date" label="Date (optionnel)" wire:model="paid_at" />
            </div>

            <div>
                <label for="notes" class="mb-1 block text-sm font-medium text-zinc-700">Notes</label>
                <textarea id="notes" wire:model="notes" rows="2"
                    class="block w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-zinc-800/20"></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="filled">Enregistrer le règlement</flux:button>
            </div>
        </form>
    @endcan

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Montant</th>
                    <th class="px-4 py-3">Moyen</th>
                    <th class="px-4 py-3">Référence</th>
                    <th class="px-4 py-3">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($facture->reglements as $reglement)
                    <tr wire:key="reglement-{{ $reglement->id }}">
                        <td class="px-4 py-3 text-zinc-600">{{ $reglement->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800">{{ number_format((float) $reglement->montant, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $reglement->moyen_paiement }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $reglement->reference ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $reglement->statut }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-400">Aucun règlement enregistré.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

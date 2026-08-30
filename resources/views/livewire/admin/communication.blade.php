<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <h1 class="mb-6 text-xl font-semibold text-zinc-800">Communication</h1>

    <form wire:submit="envoyer" class="max-w-lg space-y-4">
        <p class="text-sm text-zinc-500">Cette annonce sera envoyée par email au directeur de chaque école active ou suspendue.</p>

        <x-ui.input name="sujet" label="Sujet" wire:model="sujet" required />

        <div>
            <label for="contenu" class="mb-1 block text-sm font-medium text-zinc-700">Message</label>
            <textarea
                id="contenu"
                wire:model="contenu"
                rows="6"
                class="block w-full rounded-lg border px-3 py-2 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-zinc-800/20 {{ $errors->has('contenu') ? 'border-red-300' : 'border-zinc-200' }}"
            ></textarea>
            @error('contenu') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <flux:button type="submit" variant="filled">Envoyer à toutes les écoles</flux:button>
    </form>

    <div class="mt-10 max-w-2xl">
        <h2 class="mb-3 text-sm font-semibold text-zinc-800">Historique récent</h2>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Sujet</th>
                        <th class="px-4 py-2">Destinataires</th>
                        <th class="px-4 py-2">Envoyé par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($historique as $entree)
                        <tr wire:key="log-{{ $entree->id }}">
                            <td class="px-4 py-2 text-zinc-600">{{ $entree->envoye_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 font-medium text-zinc-800">{{ $entree->sujet }}</td>
                            <td class="px-4 py-2 text-zinc-600">{{ $entree->destinataires }}</td>
                            <td class="px-4 py-2 text-zinc-600">{{ $entree->envoye_par }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-zinc-400">Aucune annonce envoyée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

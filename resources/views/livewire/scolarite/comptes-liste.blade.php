<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-800">Comptes staff</h1>

        @can('create', \App\Models\User::class)
            <flux:button wire:click="$set('creating', true)" variant="filled">
                Créer un compte
            </flux:button>
        @endcan
    </div>

    <div class="mb-4">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Rechercher par nom ou email…"
            class="w-full max-w-sm rounded-lg border border-zinc-200 px-3 py-2 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-zinc-800/20"
        />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Rôle</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($comptes as $compte)
                    <tr wire:key="compte-{{ $compte->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-800">{{ $compte->name }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $compte->email }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $compte->getRoleNames()->first() }}</td>
                        <td class="px-4 py-3">
                            @if ($compte->personne->actif)
                                <span class="text-green-600">Actif</span>
                            @else
                                <span class="text-zinc-400">Désactivé</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @can('update', $compte)
                                    @if ($compte->personne->actif)
                                        <flux:button wire:click="confirmDisable('{{ $compte->id }}')" variant="ghost" size="sm" class="text-red-600">
                                            Désactiver
                                        </flux:button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-400">Aucun compte trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $comptes->links() }}
    </div>

    <x-ui.modal name="creating" title="Créer un compte staff">
        <form wire:submit="creer" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700">Nom</label>
                <input type="text" wire:model="nom" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm" />
                @error('nom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">Prénom</label>
                <input type="text" wire:model="prenom" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm" />
                @error('prenom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">Email</label>
                <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm" />
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">Téléphone</label>
                <input type="text" wire:model="telephone" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm" />
                @error('telephone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">Rôle</label>
                <select wire:model="role" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                    <option value="PROF">Professeur</option>
                    <option value="SCOLARITE">Scolarité</option>
                    <option value="COMPTABLE">Comptable</option>
                    <option value="SECRETAIRE">Secrétaire</option>
                </select>
                @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('creating', false)" variant="ghost">Annuler</flux:button>
                <flux:button type="submit" variant="filled">Créer</flux:button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal name="confirmingDisableId" title="Désactiver ce compte ?">
        <p class="text-sm text-zinc-600">
            Le compte perdra immédiatement l'accès à la plateforme. Les données déjà saisies (notes,
            séances…) sont conservées.
        </p>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="$set('confirmingDisableId', null)" variant="ghost">Annuler</flux:button>
            <flux:button wire:click="disable" variant="danger">Désactiver</flux:button>
        </div>
    </x-ui.modal>
</div>

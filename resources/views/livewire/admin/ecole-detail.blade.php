<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:button :href="route('admin.portail.ecoles.liste')" variant="ghost" size="sm">&larr; Écoles</flux:button>
            <h1 class="mt-2 text-xl font-semibold text-zinc-800">{{ $etablissement->nom }}</h1>
        </div>

        <span @class([
            'rounded-full px-3 py-1 text-xs font-medium',
            'bg-green-50 text-green-700' => $etablissement->statut === 'ACTIF',
            'bg-amber-50 text-amber-700' => $etablissement->statut === 'SUSPENDU',
            'bg-zinc-100 text-zinc-500' => $etablissement->statut === 'ARCHIVE',
        ])>
            {{ $etablissement->statut }}
        </span>
    </div>

    <div class="mb-6 flex gap-1 border-b border-zinc-200 text-sm">
        @foreach (['infos' => 'Infos', 'personnalisation' => 'Personnalisation', 'abonnement' => 'Abonnement', 'stats' => 'Stats', 'acces' => 'Accès'] as $tab => $label)
            <button
                type="button"
                wire:click="setActiveTab('{{ $tab }}')"
                @class([
                    'border-b-2 px-4 py-2 -mb-px',
                    'border-zinc-800 font-medium text-zinc-900' => $activeTab === $tab,
                    'border-transparent text-zinc-500 hover:text-zinc-800' => $activeTab !== $tab,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($activeTab === 'infos')
        <form wire:submit="updateInfos" class="max-w-lg space-y-4">
            <x-ui.input name="nom" label="Nom de l'école" wire:model="nom" />
            <x-ui.input name="adresse" label="Adresse" wire:model="adresse" />
            <x-ui.input name="ville" label="Ville" wire:model="ville" />
            <x-ui.input name="pays" label="Pays" wire:model="pays" />
            <x-ui.input name="telephone" label="Téléphone" wire:model="telephone" />
            <x-ui.input name="telephone_ecole" label="Téléphone école" wire:model="telephone_ecole" />
            <x-ui.input name="email" type="email" label="Email" wire:model="email" />
            <x-ui.input name="contact_directeur" label="Contact directeur" wire:model="contact_directeur" />

            <flux:button type="submit" variant="filled">Enregistrer</flux:button>
        </form>
    @elseif ($activeTab === 'personnalisation')
        <form wire:submit="updatePersonnalisation" class="max-w-lg space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-ui.input name="couleur_primaire" type="color" label="Couleur primaire" wire:model="couleur_primaire" />
                <x-ui.input name="couleur_secondaire" type="color" label="Couleur secondaire" wire:model="couleur_secondaire" />
            </div>
            <x-ui.input name="nom_court" label="Nom court" wire:model="nom_court" />
            <x-ui.input name="slogan" label="Slogan" wire:model="slogan" />

            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700">Logo</label>

                @if ($etablissement->logo_url)
                    <img src="{{ $etablissement->logo_url }}" alt="Logo actuel" class="mb-2 h-12 w-12 rounded-lg border border-zinc-200 object-contain">
                @endif

                <input type="file" wire:model="logo" accept="image/png,image/jpeg" class="block w-full text-sm">

                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" alt="Aperçu" class="mt-2 h-12 w-12 rounded-lg border border-zinc-200 object-contain">
                @endif

                @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <flux:button type="submit" variant="filled">Enregistrer</flux:button>
        </form>
    @elseif ($activeTab === 'abonnement')
        <form wire:submit="updateAbonnement" class="max-w-lg space-y-4">
            <x-ui.select
                name="plan_id"
                label="Plan tarifaire"
                wire:model="plan_id"
                placeholder="Aucun plan"
                :options="$plans->pluck('nom', 'id')"
            />
            <x-ui.input name="nb_eleves_max" type="number" label="Nombre d'élèves max" wire:model="nb_eleves_max" />
            <x-ui.input name="stockage_max_go" type="number" label="Stockage max (Go)" wire:model="stockage_max_go" />

            <flux:button type="submit" variant="filled">Enregistrer</flux:button>
        </form>

        <div class="mt-8 max-w-lg">
            <h2 class="mb-3 text-sm font-semibold text-zinc-800">Derniers règlements</h2>

            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-4 py-2">Mois</th>
                            <th class="px-4 py-2">Montant</th>
                            <th class="px-4 py-2">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($reglements as $reglement)
                            <tr>
                                <td class="px-4 py-2">{{ $reglement->mois }}</td>
                                <td class="px-4 py-2">{{ number_format((float) $reglement->montant, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-2">{{ $reglement->statut }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-zinc-400">Aucun règlement enregistré.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($activeTab === 'stats')
        <div class="grid max-w-lg grid-cols-2 gap-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-zinc-500">Élèves inscrits</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-800">{{ $nbElevesActuel }} / {{ $etablissement->nb_eleves_max }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-zinc-500">Stockage max</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-800">{{ $etablissement->stockage_max_go }} Go</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-zinc-500">Créée le</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-800">{{ $etablissement->created_at?->format('d/m/Y') ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-zinc-500">Fin d'essai</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-800">{{ $etablissement->date_fin_essai?->format('d/m/Y') ?? '—' }}</p>
            </div>
        </div>
    @elseif ($activeTab === 'acces')
        <div class="max-w-lg space-y-4">
            @if ($etablissement->statut === 'ACTIF')
                <div class="rounded-xl border border-zinc-200 bg-white p-4">
                    <p class="text-sm text-zinc-600">Cette école est active.</p>
                    <flux:button wire:click="confirmSuspend" variant="danger" class="mt-3">Suspendre</flux:button>
                </div>
            @elseif ($etablissement->statut === 'SUSPENDU')
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm text-amber-700">Suspendue le {{ $etablissement->date_suspension?->format('d/m/Y') }}.</p>
                    <p class="mt-1 text-sm text-amber-700">Motif : {{ $etablissement->motif_suspension }}</p>
                    <div class="mt-3 flex gap-2">
                        <flux:button wire:click="reactiver" variant="filled">Réactiver</flux:button>
                        <flux:button wire:click="confirmArchive" variant="danger">Archiver</flux:button>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                    <p class="text-sm text-zinc-500">Cette école est archivée. Accès définitivement coupé.</p>
                </div>
            @endif
        </div>
    @endif

    <x-ui.modal name="confirmingSuspend" title="Suspendre cette école ?">
        <p class="mb-3 text-sm text-zinc-600">L'accès sera bloqué immédiatement. Les données sont préservées.</p>

        <textarea
            wire:model="motifSuspension"
            rows="3"
            placeholder="Motif de la suspension…"
            class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-zinc-800/20"
        ></textarea>
        @error('motifSuspension') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="$set('confirmingSuspend', false)" variant="ghost">Annuler</flux:button>
            <flux:button wire:click="suspendre" variant="danger">Suspendre</flux:button>
        </div>
    </x-ui.modal>

    <x-ui.modal name="confirmingArchive" title="Archiver définitivement cette école ?">
        <p class="text-sm text-zinc-600">Les données seront exportées vers le stockage froid et l'accès sera définitivement coupé. Cette action est irréversible.</p>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="$set('confirmingArchive', false)" variant="ghost">Annuler</flux:button>
            <flux:button wire:click="archiver" variant="danger">Archiver</flux:button>
        </div>
    </x-ui.modal>
</div>

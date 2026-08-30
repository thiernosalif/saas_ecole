<div class="max-w-2xl">
    <h1 class="mb-6 text-xl font-semibold text-zinc-800">
        {{ $eleve ? 'Modifier l\'élève' : 'Ajouter un élève' }}
    </h1>

    <form wire:submit="save" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <x-ui.input name="nom" label="Nom" wire:model="nom" required />
            <x-ui.input name="prenom" label="Prénom" wire:model="prenom" required />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-ui.input name="date_naissance" type="date" label="Date de naissance" wire:model="date_naissance" required />
            <x-ui.input name="lieu_naissance" label="Lieu de naissance" wire:model="lieu_naissance" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-ui.select name="genre" label="Genre" wire:model="genre" placeholder="—" :options="['M' => 'Masculin', 'F' => 'Féminin']" />
            <x-ui.input name="matricule" label="Matricule" wire:model="matricule" />
        </div>

        <x-ui.input name="num_acte_naissance" label="N° acte de naissance" wire:model="num_acte_naissance" required />

        <div class="grid grid-cols-2 gap-4">
            <x-ui.input name="telephone" label="Téléphone" wire:model="telephone" />
            <x-ui.input name="email" type="email" label="Email" wire:model="email" />
        </div>

        <x-ui.input name="adresse" label="Adresse" wire:model="adresse" />

        <div class="grid grid-cols-2 gap-4">
            <x-ui.input name="nationalite" label="Nationalité" wire:model="nationalite" />
            <x-ui.input name="groupe_sanguin" label="Groupe sanguin" wire:model="groupe_sanguin" />
        </div>

        <x-ui.input name="allergies" label="Allergies" wire:model="allergies" />

        @if ($eleve)
            <x-ui.checkbox name="actif" label="Élève actif" wire:model="actif" />
        @endif

        <div class="flex justify-end gap-2 pt-4">
            <flux:button :href="route('scolarite.eleves.index')" variant="ghost">Annuler</flux:button>
            <flux:button type="submit" variant="filled">Enregistrer</flux:button>
        </div>
    </form>

    <div class="mt-10 border-t border-zinc-200 pt-6">
        <h2 class="mb-4 text-lg font-semibold text-zinc-800">Parents &amp; accès</h2>

        @if ($eleve)
            <div class="mb-6 flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3">
                <div>
                    <p class="text-sm font-medium text-zinc-800">Accès élève</p>
                    <p class="text-xs text-zinc-500">
                        @if ($eleveADejaCompte)
                            Un compte existe déjà pour cet élève.
                        @else
                            Aucun compte pour l'instant — optionnel, à activer école par école.
                        @endif
                    </p>
                </div>
                @unless ($eleveADejaCompte)
                    <flux:button wire:click="creerAccesEleve" variant="ghost" size="sm">
                        Créer un accès élève
                    </flux:button>
                @endunless
            </div>

            @if (count($parentsExistants) > 0)
                <div class="mb-6 space-y-2">
                    @foreach ($parentsExistants as $parent)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-3" wire:key="parent-existant-{{ $parent['parent_id'] }}">
                            <div>
                                <p class="text-sm font-medium text-zinc-800">
                                    {{ $parent['prenom'] }} {{ $parent['nom'] }}
                                    @if ($parent['lien'])
                                        <span class="text-zinc-400">({{ $parent['lien'] }})</span>
                                    @endif
                                </p>
                                <p class="text-xs text-zinc-500">{{ $parent['email'] ?: "Pas d'email" }} · {{ $parent['telephone'] ?: '—' }}</p>
                            </div>
                            @if ($parent['compte_existe'])
                                <span class="text-xs font-medium text-green-600">Accès actif</span>
                            @else
                                <flux:button wire:click="creerAccesParent('{{ $parent['parent_id'] }}')" variant="ghost" size="sm">
                                    Créer un accès parent
                                </flux:button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="space-y-4">
            @foreach ($nouveauxParents as $index => $parent)
                <div class="rounded-lg border border-zinc-200 p-4" wire:key="nouveau-parent-{{ $index }}">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-medium text-zinc-700">Nouveau parent</p>
                        <button type="button" wire:click="retirerParent({{ $index }})" class="text-xs text-red-600">Retirer</button>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.input name="nouveauxParents.{{ $index }}.nom" label="Nom" wire:model="nouveauxParents.{{ $index }}.nom" />
                        <x-ui.input name="nouveauxParents.{{ $index }}.prenom" label="Prénom" wire:model="nouveauxParents.{{ $index }}.prenom" />
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <x-ui.input name="nouveauxParents.{{ $index }}.telephone" label="Téléphone" wire:model="nouveauxParents.{{ $index }}.telephone" />
                        <x-ui.input name="nouveauxParents.{{ $index }}.email" type="email" label="Email" wire:model="nouveauxParents.{{ $index }}.email" />
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <x-ui.select
                            name="nouveauxParents.{{ $index }}.lien"
                            label="Lien"
                            wire:model="nouveauxParents.{{ $index }}.lien"
                            placeholder="—"
                            :options="['PERE' => 'Père', 'MERE' => 'Mère', 'TUTEUR' => 'Tuteur']"
                        />
                        <div class="flex flex-col justify-center gap-2">
                            <x-ui.checkbox name="nouveauxParents.{{ $index }}.tuteur_principal" label="Tuteur principal" wire:model="nouveauxParents.{{ $index }}.tuteur_principal" />
                            <x-ui.checkbox name="nouveauxParents.{{ $index }}.contact_urgence" label="Contact d'urgence" wire:model="nouveauxParents.{{ $index }}.contact_urgence" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-ui.checkbox
                            name="nouveauxParents.{{ $index }}.creer_compte"
                            label="Créer un accès pour ce parent dès l'enregistrement (email requis)"
                            wire:model="nouveauxParents.{{ $index }}.creer_compte"
                        />
                    </div>
                </div>
            @endforeach

            <flux:button type="button" wire:click="ajouterParent" variant="ghost" size="sm">
                + Ajouter un parent
            </flux:button>
        </div>
    </div>
</div>

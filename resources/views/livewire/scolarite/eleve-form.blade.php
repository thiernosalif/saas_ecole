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
</div>

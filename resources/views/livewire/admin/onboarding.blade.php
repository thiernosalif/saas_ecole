<div>
    <h1 class="mb-6 text-xl font-semibold text-zinc-800">Nouvelle école</h1>

    <ol class="mb-8 flex max-w-2xl flex-wrap gap-4 text-sm">
        @foreach (\App\Domain\SuperAdmin\Livewire\Onboarding::ETAPES as $numero => $label)
            <li @class([
                'flex items-center gap-2',
                'font-medium text-zinc-900' => $numero === $step,
                'text-green-600' => $numero < $step,
                'text-zinc-400' => $numero > $step,
            ])>
                <span @class([
                    'flex size-6 items-center justify-center rounded-full text-xs',
                    'bg-zinc-800 text-white' => $numero === $step,
                    'bg-green-100 text-green-700' => $numero < $step,
                    'bg-zinc-100 text-zinc-400' => $numero > $step,
                ])>
                    {{ $numero < $step ? '✓' : $numero }}
                </span>
                {{ $label }}
            </li>
        @endforeach
    </ol>

    @if ($step === 1)
        <form wire:submit="etapeSuivante" class="max-w-lg space-y-4">
            <x-ui.input name="nom" label="Nom de l'école" wire:model="nom" required autofocus />
            <x-ui.input name="adresse" label="Adresse" wire:model="adresse" />
            <x-ui.input name="ville" label="Ville" wire:model="ville" />
            <x-ui.input name="telephone_ecole" label="Téléphone école" wire:model="telephone_ecole" />
            <x-ui.input name="contact_directeur" label="Nom du directeur" wire:model="contact_directeur" />
            <x-ui.input name="email_directeur" type="email" label="Email du directeur" wire:model="email_directeur" required />

            <x-ui.select
                name="plan_id"
                label="Plan tarifaire"
                wire:model="plan_id"
                placeholder="Aucun plan pour l'instant"
                :options="$plans->pluck('nom', 'id')"
            />

            <x-ui.checkbox name="essai_gratuit" label="Démarrer un essai gratuit de 30 jours" wire:model="essai_gratuit" />

            <flux:button type="submit" variant="filled">Suivant</flux:button>
        </form>
    @elseif ($step === 2)
        <form wire:submit="terminer" class="max-w-lg space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700">Logo (optionnel)</label>
                <input type="file" wire:model="logo" accept="image/png,image/jpeg" class="block w-full text-sm">

                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" alt="Aperçu du logo" class="mt-2 h-16 w-16 rounded-lg border border-zinc-200 object-contain">
                @endif

                @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-ui.input name="couleur_primaire" type="color" label="Couleur primaire" wire:model="couleur_primaire" />
                <x-ui.input name="couleur_secondaire" type="color" label="Couleur secondaire" wire:model="couleur_secondaire" />
            </div>

            <div
                class="rounded-xl border border-zinc-200 p-4"
                style="background-color: {{ $couleur_secondaire ?: '#fafafa' }};"
            >
                <p class="text-sm font-medium" style="color: {{ $couleur_primaire ?: '#18181b' }};">Aperçu des couleurs</p>
            </div>

            <div class="flex gap-2">
                <flux:button type="button" wire:click="etapePrecedente" variant="ghost">Précédent</flux:button>
                <flux:button type="submit" variant="filled">Créer l'école</flux:button>
            </div>
        </form>
    @elseif ($step === 5 && $recapitulatif)
        <div class="max-w-lg space-y-4">
            <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                École créée avec succès.
            </div>

            <dl class="divide-y divide-zinc-100 rounded-xl border border-zinc-200 bg-white">
                <div class="flex justify-between px-4 py-3 text-sm">
                    <dt class="text-zinc-500">École</dt>
                    <dd class="font-medium text-zinc-800">{{ $recapitulatif['ecole'] }}</dd>
                </div>
                <div class="flex justify-between px-4 py-3 text-sm">
                    <dt class="text-zinc-500">Sous-domaine</dt>
                    <dd class="font-medium text-zinc-800">{{ $recapitulatif['sous_domaine'] }}</dd>
                </div>
                <div class="flex justify-between px-4 py-3 text-sm">
                    <dt class="text-zinc-500">URL</dt>
                    <dd class="font-medium text-zinc-800">{{ $recapitulatif['url'] }}</dd>
                </div>
                <div class="flex justify-between px-4 py-3 text-sm">
                    <dt class="text-zinc-500">Plan</dt>
                    <dd class="font-medium text-zinc-800">{{ $recapitulatif['plan'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between px-4 py-3 text-sm">
                    <dt class="text-zinc-500">Essai jusqu'au</dt>
                    <dd class="font-medium text-zinc-800">{{ $recapitulatif['essai_jusquau'] ?? '—' }}</dd>
                </div>
            </dl>

            <p class="text-sm text-zinc-500">
                Le compte directeur a été créé et une invitation par email a été envoyée.
            </p>

            <div class="flex gap-2">
                <flux:button :href="route('admin.portail.ecoles.liste')" variant="filled">Retour aux écoles</flux:button>
                <flux:button wire:click="recommencer" variant="ghost">Créer une autre école</flux:button>
            </div>
        </div>
    @endif
</div>

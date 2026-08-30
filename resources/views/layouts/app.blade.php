<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-56 shrink-0 border-r border-zinc-200 bg-white px-4 py-6 md:block">
            <p class="mb-6 px-2 text-sm font-semibold text-zinc-800">{{ app('currentTenant')->nom ?? config('app.name') }}</p>

            <nav class="space-y-1 text-sm">
                @can('viewAny', \App\Domain\Scolarite\Models\Personne::class)
                    <a href="{{ route('scolarite.eleves.index') }}"
                       class="block rounded-lg px-3 py-2 {{ request()->routeIs('scolarite.eleves.*') ? 'bg-zinc-800/5 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-800/5' }}">
                        Élèves
                    </a>
                @endcan

                @can('viewAny', \App\Domain\Scolarite\Models\Classe::class)
                    <a href="{{ route('scolarite.classes.index') }}"
                       class="block rounded-lg px-3 py-2 {{ request()->routeIs('scolarite.classes.*') ? 'bg-zinc-800/5 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-800/5' }}">
                        Classes
                    </a>
                @endcan

                @can('viewAny', \App\Domain\Scolarite\Models\Absence::class)
                    <a href="{{ route('scolarite.absences.saisie') }}"
                       class="block rounded-lg px-3 py-2 {{ request()->routeIs('scolarite.absences.*') ? 'bg-zinc-800/5 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-800/5' }}">
                        Absences
                    </a>
                @endcan

                @can('viewAny', \App\Domain\Planning\Models\Seance::class)
                    <a href="{{ route('planning.emploi-du-temps.index') }}"
                       class="block rounded-lg px-3 py-2 {{ request()->routeIs('planning.emploi-du-temps.*') ? 'bg-zinc-800/5 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-800/5' }}">
                        Emploi du temps
                    </a>
                @endcan

                @can('viewAny', \App\Domain\Planning\Models\Devoir::class)
                    <a href="{{ route('planning.devoirs.index') }}"
                       class="block rounded-lg px-3 py-2 {{ request()->routeIs('planning.devoirs.*') ? 'bg-zinc-800/5 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-800/5' }}">
                        Devoirs
                    </a>
                @endcan

                @can('viewAny', \App\Domain\Planning\Models\Examen::class)
                    <a href="{{ route('planning.examens.index') }}"
                       class="block rounded-lg px-3 py-2 {{ request()->routeIs('planning.examens.*') ? 'bg-zinc-800/5 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-800/5' }}">
                        Examens
                    </a>
                @endcan
            </nav>
        </aside>

        <div class="flex flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-zinc-200 bg-white px-6 py-3">
                <p class="text-sm text-zinc-500">{{ auth()->user()->name ?? '' }}</p>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" size="sm">Déconnexion</flux:button>
                </form>
            </header>

            <main class="flex-1 p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @fluxScripts
    @livewireScripts
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Portail Super Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-56 shrink-0 border-r border-zinc-200 bg-white px-4 py-6 md:block">
            <p class="mb-6 px-2 text-sm font-semibold text-zinc-800">Portail Super Admin</p>

            <nav class="space-y-1 text-sm">
                <a href="{{ route('admin.portail.ecoles.liste') }}"
                   class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.portail.ecoles.*') ? 'bg-zinc-800/5 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-800/5' }}">
                    Écoles
                </a>

                <a href="{{ route('admin.portail.onboarding') }}"
                   class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.portail.onboarding') ? 'bg-zinc-800/5 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-800/5' }}">
                    Onboarding
                </a>

                <a href="{{ route('admin.portail.analytics') }}"
                   class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.portail.analytics') ? 'bg-zinc-800/5 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-800/5' }}">
                    Analytics
                </a>

                <a href="{{ route('admin.portail.communication') }}"
                   class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.portail.communication') ? 'bg-zinc-800/5 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-800/5' }}">
                    Communication
                </a>
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

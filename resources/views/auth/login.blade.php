<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="bg-zinc-50 text-zinc-900 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4">
        <div class="w-full max-w-sm rounded-xl border border-zinc-200 bg-white p-8 shadow-sm">
            <h1 class="mb-6 text-lg font-semibold text-zinc-800">
                {{ request()->getHost() === config('app.admin_subdomain') ? 'Connexion au portail Super Admin' : "Connexion à l'espace école" }}
            </h1>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <x-ui.input name="email" type="email" label="Email" value="{{ old('email') }}" required autofocus />
                <x-ui.input name="password" type="password" label="Mot de passe" required />

                <x-ui.checkbox name="remember" label="Se souvenir de moi" />

                <flux:button type="submit" variant="filled" class="w-full justify-center">
                    Se connecter
                </flux:button>
            </form>
        </div>
    </div>
    @fluxScripts
</body>
</html>

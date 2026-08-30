@props([
    'name',
    'title' => null,
])

<div
    x-data="{ show: @entangle($name) }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center px-4"
>
    <div class="fixed inset-0 bg-zinc-900/40" x-on:click="show = false"></div>

    <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-lg">
        @if ($title)
            <h2 class="mb-4 text-base font-semibold text-zinc-800">{{ $title }}</h2>
        @endif

        {{ $slot }}
    </div>
</div>

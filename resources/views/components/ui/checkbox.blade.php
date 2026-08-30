@props([
    'label' => null,
    'name',
])

<label for="{{ $name }}" class="flex items-center gap-2 text-sm text-zinc-700">
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="checkbox"
        value="1"
        {{ $attributes->merge(['class' => 'size-4 rounded border-zinc-300 text-zinc-800 focus:ring-zinc-800/20']) }}
    />
    {{ $label }}
</label>

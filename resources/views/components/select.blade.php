@props(['name', 'label' => null, 'id' => null, 'placeholder' => null])

@php($id ??= $name)

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif

    <select name="{{ $name }}" id="{{ $id }}"
        {{ $attributes->merge(['class' => 'w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-700 focus:border-primary focus:bg-white focus:ring-primary']) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>

    {{-- diisi via JS saat validasi 422 --}}
    <p data-error="{{ $name }}" class="mt-1 hidden text-xs text-red-600"></p>
</div>

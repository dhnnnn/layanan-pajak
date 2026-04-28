@props(['label', 'active' => false, 'open' => null])

@php
    // Default: buka jika ada item aktif di dalamnya, atau jika $open di-set true
    $isOpen = $open ?? true;
@endphp

<div x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }">
    <button @click="open = !open" type="button"
            class="w-full flex items-center justify-between px-3 pt-4 pb-1 group">
        <span class="text-xs font-semibold uppercase tracking-wider transition-colors"
              :class="open ? 'text-slate-400' : 'text-slate-600'">{{ $label }}</span>
        <svg class="w-3.5 h-3.5 text-slate-600 transition-transform duration-200 group-hover:text-slate-400"
             :class="open ? 'rotate-180 text-slate-400' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div x-show="open" x-collapse>
        {{ $slot }}
    </div>
</div>

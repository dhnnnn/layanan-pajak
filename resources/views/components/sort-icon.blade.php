@props(['column', 'activeCol', 'activeDir'])

<div class="flex flex-col items-center leading-none {{ $activeCol === $column ? 'opacity-100' : 'opacity-40 group-hover:opacity-100' }} transition-all">
    @php
        $isAsc = ($activeCol === $column && $activeDir === 'asc');
        $isDesc = ($activeCol === $column && $activeDir === 'desc');
    @endphp
    
    <svg class="w-2.5 h-2.5 {{ $isAsc ? 'text-blue-600' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isAsc ? '4' : '3' }}" d="M5 15l7-7 7 7"/>
    </svg>
    <svg class="w-2.5 h-2.5 {{ $isDesc ? 'text-blue-600' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isDesc ? '4' : '3' }}" d="M19 9l-7 7-7-7"/>
    </svg>
</div>

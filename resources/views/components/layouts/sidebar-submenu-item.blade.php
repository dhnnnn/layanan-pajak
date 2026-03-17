@props(['route', 'active' => false])

<a href="{{ route($route) }}" 
   class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors {{ $active ? 'text-white font-medium' : 'text-slate-400 hover:text-slate-200' }}">
    <svg class="w-1.5 h-1.5 shrink-0" fill="currentColor" viewBox="0 0 8 8">
        <circle cx="4" cy="4" r="3"/>
    </svg>
    {{ $slot }}
</a>

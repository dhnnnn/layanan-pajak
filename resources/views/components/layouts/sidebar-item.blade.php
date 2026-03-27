@props(['route', 'params' => [], 'active' => false, 'activeClass' => 'bg-blue-600 text-white'])

<a href="{{ route($route, $params) }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ $active ? $activeClass : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
    {{ $slot }}
</a>

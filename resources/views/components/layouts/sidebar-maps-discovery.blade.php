{{-- Dynamic Maps Discovery menu based on permission --}}
@can('manage maps-discovery')
<x-layouts.sidebar-dropdown
    label="Potensi Wajib Pajak"
    :active="request()->routeIs('admin.maps-discovery.*')"
>
    <x-slot:icon>
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </x-slot:icon>
    <x-layouts.sidebar-submenu-item route="admin.maps-discovery.index" :active="request()->routeIs('admin.maps-discovery.index')">
        Pengambilan Data Potensi
    </x-layouts.sidebar-submenu-item>
    <x-layouts.sidebar-submenu-item route="admin.maps-discovery.report" :active="request()->routeIs('admin.maps-discovery.report') || request()->routeIs('admin.maps-discovery.report-detail')">
        Data Potensi Wajib Pajak
    </x-layouts.sidebar-submenu-item>
</x-layouts.sidebar-dropdown>
@elsecan('view maps-discovery')
<x-layouts.sidebar-item route="admin.maps-discovery.report" :active="request()->routeIs('admin.maps-discovery.*')">
    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    Potensi Wajib Pajak
</x-layouts.sidebar-item>
@endcan

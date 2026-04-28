<x-layouts.app :title="$title" :header="$header">
    <x-slot:sidebar>
        {{-- Overlay mobile --}}
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-40 lg:hidden"
             style="display: none;">
        </div>

        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed lg:relative inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white flex flex-col flex-shrink-0 transform transition-transform duration-300 ease-in-out">

            {{-- Tombol close mobile --}}
            <button @click="sidebarOpen = false"
                    class="lg:hidden absolute top-4 right-4 p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors z-10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Logo --}}
            <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700">
                <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-sm shrink-0">M</div>
                <div class="min-w-0">
                    <p class="font-semibold text-sm leading-tight">MANTRA</p>
                    <p class="text-slate-400 text-xs">Kab. Pasuruan</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto text-sm scrollbar-hide">

                <p class="px-3 pt-1 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Menu Utama</p>

                <x-layouts.sidebar-item route="admin.dashboard" :active="request()->routeIs('admin.dashboard')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </x-layouts.sidebar-item>

                {{-- Admin --}}
                @if(auth()->user()->isAdmin() && !auth()->user()->isKepalaUpt())
                <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kelola Data</p>

                <x-layouts.sidebar-item route="admin.tax-types.index" :active="request()->routeIs('admin.tax-types.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Kategori Pajak
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.districts.index" :active="request()->routeIs('admin.districts.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Data Wilayah
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.employees.index" :active="request()->routeIs('admin.employees.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Data Petugas
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.upts.index" :active="request()->routeIs('admin.upts.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Unit Pelayanan
                </x-layouts.sidebar-item>

                @can('view additional-targets')
                <x-layouts.sidebar-dropdown 
                    label="Target Tambahan" 
                    :active="request()->routeIs('admin.*additional-targets.*')"
                >
                    <x-slot:icon>
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </x-slot:icon>
                    <x-layouts.sidebar-submenu-item route="admin.upt-additional-targets.index" :active="request()->routeIs('admin.upt-additional-targets.*')">
                        Target APBD
                    </x-layouts.sidebar-submenu-item>
                    <x-layouts.sidebar-submenu-item route="admin.district-additional-targets.index" :active="request()->routeIs('admin.district-additional-targets.*')">
                        Target Kecamatan
                    </x-layouts.sidebar-submenu-item>
                </x-layouts.sidebar-dropdown>
                @endcan

                <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Laporan & Pantauan</p>

                <x-layouts.sidebar-maps-discovery />

                <x-layouts.sidebar-item route="admin.forecasting.index" :active="request()->routeIs('admin.forecasting.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    Prediksi Penerimaan
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.tax-targets.report" :active="request()->routeIs('admin.tax-targets.report')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Laporan Anggaran
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.realization-monitoring.index" :active="request()->routeIs('admin.realization-monitoring.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Realisasi Penerimaan
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.monitoring.index" :active="request()->routeIs('admin.monitoring.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Pemantau Wajib Pajak
                </x-layouts.sidebar-item>

                <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Manajemen Akses</p>

                <x-layouts.sidebar-item route="admin.roles.index" :active="request()->routeIs('admin.roles.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Role
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.rbac-users.index" :active="request()->routeIs('admin.rbac-users.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Kelola User
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.access-monitoring.index" :active="request()->routeIs('admin.access-monitoring.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Monitoring Akses
                </x-layouts.sidebar-item>
                @endif

                {{-- Kepala UPT --}}
                @if(auth()->user()->isKepalaUpt())
                <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">UPT Anda</p>

                <x-layouts.sidebar-item route="admin.upts.show" :params="['upt' => auth()->user()->upt()?->id]" :active="request()->routeIs('admin.upts.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Data UPT
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.employees.index" :active="request()->routeIs('admin.employees.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Kinerja Petugas
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.monitoring.index" :active="request()->routeIs('admin.monitoring.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Pemantau Wajib Pajak
                </x-layouts.sidebar-item>

                @can('view forecasting')
                <x-layouts.sidebar-item route="admin.forecasting.index" :active="request()->routeIs('admin.forecasting.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    Prediksi Penerimaan
                </x-layouts.sidebar-item>
                @endcan

                <x-layouts.sidebar-maps-discovery />
                @endif

                {{-- Pemimpin --}}
                @if(auth()->user()->hasRole('pemimpin'))
                <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Laporan & Pantauan</p>

                <x-layouts.sidebar-maps-discovery />

                <x-layouts.sidebar-item route="admin.forecasting.index" :active="request()->routeIs('admin.forecasting.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    Prediksi Penerimaan
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.tax-targets.report" :active="request()->routeIs('admin.tax-targets.report')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Laporan Anggaran
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.realization-monitoring.index" :active="request()->routeIs('admin.realization-monitoring.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Realisasi Penerimaan
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.monitoring.index" :active="request()->routeIs('admin.monitoring.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Pemantau Wajib Pajak
                </x-layouts.sidebar-item>
                @endif

            </nav>

            {{-- User + Logout --}}
            <div class="px-4 py-4 border-t border-slate-700">
                <p class="text-sm text-white font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-400 mb-3">
                    @if(auth()->user()->isKepalaUpt())
                        Kepala {{ auth()->user()->upt()?->name ?? 'UPT' }}
                    @elseif(auth()->user()->hasRole('pemimpin'))
                        Pemimpin
                    @elseif(auth()->user()->isAdmin())
                        Administrator
                    @else
                        Pegawai
                    @endif
                </p>
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-1.5 text-xs text-slate-400 hover:text-white transition-colors mb-2 {{ request()->routeIs('profile.*') ? 'text-white' : '' }}">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Edit Profil
                </a>
                <a href="{{ route('logout.get') }}" class="flex items-center gap-1.5 text-xs text-red-400 hover:text-red-300 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Keluar
                </a>
            </div>

        </aside>
    </x-slot:sidebar>

    <x-slot:headerActions>
        {{ $headerActions ?? '' }}
    </x-slot:headerActions>

    {{ $slot }}
</x-layouts.app>

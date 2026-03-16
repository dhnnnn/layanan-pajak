<x-layouts.app :title="$title" :header="$header">
    <x-slot:sidebar>
        <aside class="w-64 bg-slate-900 text-white flex flex-col flex-shrink-0">
            {{-- Logo --}}
            <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700">
                <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-sm shrink-0">LP</div>
                <div class="min-w-0">
                    <p class="font-semibold text-sm leading-tight">Layanan Pajak</p>
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

                <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Master Data</p>
                <x-layouts.sidebar-item route="admin.tax-types.index" :active="request()->routeIs('admin.tax-types.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Jenis Pajak
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.districts.index" :active="request()->routeIs('admin.districts.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Kecamatan
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.employees.index" :active="request()->routeIs('admin.employees.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Pegawai
                </x-layouts.sidebar-item>

                <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pengelolaan</p>
                <x-layouts.sidebar-item route="admin.tax-targets.index" :active="request()->routeIs('admin.tax-targets.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Target APBD
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.import.index" :active="request()->routeIs('admin.import.*')">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import Data
                </x-layouts.sidebar-item>

                <x-layouts.sidebar-item route="admin.template.download">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Unduh Template
                </x-layouts.sidebar-item>
            </nav>

            {{-- User + Logout --}}
            <div class="px-4 py-4 border-t border-slate-700">
                <p class="text-sm text-white font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-400 mb-2">Administrator</p>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 text-xs text-red-400 hover:text-red-300 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </aside>
    </x-slot:sidebar>

    <x-slot:headerActions>
        {{ $headerActions ?? '' }}
    </x-slot:headerActions>

    {{ $slot }}
</x-layouts.app>

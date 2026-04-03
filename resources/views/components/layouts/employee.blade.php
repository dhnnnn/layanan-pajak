<x-layouts.app :title="$title" :header="$header">
    <x-slot:sidebar>
        {{-- Mobile Overlay --}}
        <div class="lg:contents">
            
            {{-- Overlay for mobile --}}
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
            <aside x-show="sidebarOpen || window.innerWidth >= 1024"
                   class="fixed lg:relative inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white flex flex-col flex-shrink-0">
                
                {{-- Close button for mobile --}}
                <button @click="sidebarOpen = false" 
                        class="lg:hidden absolute top-4 right-4 p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                
                {{-- Close button for mobile --}}
                <button @click="sidebarOpen = false" 
                        class="lg:hidden absolute top-4 right-4 p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                {{-- Logo --}}
                <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700">
                    <div class="w-9 h-9 bg-emerald-600 rounded-lg flex items-center justify-center font-bold text-sm shrink-0">LP</div>
                    <div class="min-w-0">
                        <p class="font-semibold text-sm leading-tight">Layanan Pajak</p>
                        <p class="text-slate-400 text-xs">Kab. Pasuruan</p>
                    </div>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto text-sm scrollbar-hide">
                    <p class="px-3 pt-1 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Menu Utama</p>
                    <x-layouts.sidebar-item route="field-officer.dashboard" :active="request()->routeIs('field-officer.dashboard')" activeClass="bg-emerald-600 text-white">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </x-layouts.sidebar-item>

                    <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pengelolaan</p>
                    <x-layouts.sidebar-item route="field-officer.realizations.index" :active="request()->routeIs('field-officer.realizations.*')" activeClass="bg-emerald-600 text-white">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Input Realisasi
                    </x-layouts.sidebar-item>

                </nav>

                {{-- User + Logout --}}
                <div class="px-4 py-4 border-t border-slate-700">
                    <p class="text-sm text-white font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400 mb-2">Pegawai Lapangan</p>
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
        </div>
    </x-slot:sidebar>

    <x-slot:headerActions>
        {{ $headerActions ?? '' }}
    </x-slot:headerActions>

    {{ $slot }}
</x-layouts.app>

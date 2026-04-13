<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Sistem Realisasi Pajak Kab. Pasuruan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- Mobile Overlay --}}
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

    {{-- Sidebar --}}
    <aside id="sidebar" class="w-64 bg-slate-900 text-white flex flex-col flex-shrink-0 fixed lg:relative inset-y-0 left-0 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">

        {{-- Mobile Close Button --}}
        <button class="lg:hidden absolute top-4 right-4 p-1 text-slate-400 hover:text-white" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Logo --}}

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700">
            <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-sm shrink-0">LP</div>
            <div class="min-w-0">
                <p class="font-semibold text-sm leading-tight">Layanan Pajak</p>
                <p class="text-slate-400 text-xs">Kab. Pasuruan</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto text-sm">

            <p class="px-3 pt-1 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Menu Utama</p>

            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

@if(auth()->user()->isAdmin() && !auth()->user()->isKepalaUpt())
            <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Master Data</p>

            <a href="{{ route('admin.tax-types.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.tax-types.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Jenis Pajak
            </a>

            <a href="{{ route('admin.districts.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.districts.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Kecamatan
            </a>

            <a href="{{ route('admin.employees.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.employees.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Pegawai
            </a>

            <a href="{{ route('admin.upts.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.upts.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                UPT
            </a>
            @endif

            @if(auth()->user()->isKepalaUpt())
            <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">UPT Anda</p>

            <a href="{{ route('admin.upts.show', auth()->user()->upt()?->id) }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.upts.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Data UPT
            </a>
            @endif

            <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pengelolaan</p>

            <a href="{{ route('admin.forecasting.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.forecasting.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                Prediksi Penerimaan
            </a>

            @if(auth()->user()->isAdmin() && !auth()->user()->isKepalaUpt())
            <a href="{{ route('admin.tax-targets.report') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.tax-targets.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Target APBD
            </a>

            @if(Route::has('admin.import.index'))
            <a href="{{ route('admin.import.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.import.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import Data
            </a>
            @endif


            <a href="{{ route('admin.realization-monitoring.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.realization-monitoring.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Monitoring Realisasi
            </a>
            @endif

        </nav>

        {{-- User + Logout --}}
        <div class="px-4 py-4 border-t border-slate-700">
            <p class="text-sm text-white font-medium truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-400 mb-3">
                @if(auth()->user()->isKepalaUpt())
                    Kepala {{ auth()->user()->upt()?->name ?? 'UPT' }}
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

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        <header class="bg-white border-b border-slate-200 px-4 md:px-6 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                {{-- Mobile Menu Button --}}
                <button class="lg:hidden p-2 -ml-2 text-slate-600 hover:text-slate-900" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-base font-semibold text-slate-800">@yield('header')</h1>
            </div>
            <div class="flex items-center gap-3">@yield('header-actions')</div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">

            @if(session('success'))
                <div class="mb-5 flex items-start gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 flex items-start gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')

        </main>

        {{-- Footer --}}
        <footer class="shrink-0 border-t border-slate-200 bg-white px-6 py-3 text-center text-[11px] text-slate-400">
            &copy; {{ date('Y') }} Dinas Komunikasi dan Informatika Kabupaten Pasuruan
        </footer>
    </div>

</div>

<script>
    let sidebarOpen = false;

    function openSidebar() {
        sidebarOpen = true;
        document.getElementById('sidebar').classList.remove('-translate-x-full');
        document.getElementById('sidebarOverlay').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebarOpen = false;
        document.getElementById('sidebar').classList.add('-translate-x-full');
        document.getElementById('sidebarOverlay').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function toggleSidebar() {
        sidebarOpen ? closeSidebar() : openSidebar();
    }

    // Close sidebar when resizing to desktop
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) {
            closeSidebar();
        }
    });
</script>

@stack('scripts')

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pegawai') — Sistem Realisasi Pajak Kab. Pasuruan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- Sidebar --}}
    <aside class="w-64 bg-slate-900 text-white flex flex-col flex-shrink-0">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700">
            <div class="w-9 h-9 bg-emerald-600 rounded-lg flex items-center justify-center font-bold text-sm shrink-0">LP</div>
            <div class="min-w-0">
                <p class="font-semibold text-sm leading-tight">Layanan Pajak</p>
                <p class="text-slate-400 text-xs">Kab. Pasuruan</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto text-sm">

            <p class="px-3 pt-1 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Menu</p>

            <a href="{{ route('field-officer.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('field-officer.dashboard') ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('field-officer.realizations.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('field-officer.realizations.*') ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Data Realisasi
            </a>

            <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Import</p>

            <a href="{{ route('pegawai.import.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('pegawai.import.*') ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import Data
            </a>

            <a href="{{ route('pegawai.template.download') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-slate-300 hover:bg-slate-700 hover:text-white">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Unduh Template
            </a>

        </nav>

        {{-- User + Logout --}}
        <div class="px-4 py-4 border-t border-slate-700">
            <p class="text-sm text-white font-medium truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-400 mb-2">Pegawai</p>
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-1.5 text-xs text-slate-400 hover:text-white transition-colors mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Edit Profil
            </a>
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

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shrink-0">
            <h1 class="text-base font-semibold text-slate-800">@yield('header')</h1>
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
    </div>

</div>
</body>
</html>

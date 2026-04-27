<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} — MANTRA Kab. Pasuruan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md">
            {{-- Logo / Brand --}}
            <div class="flex flex-col items-center mb-10">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center font-bold text-2xl text-white shadow-xl shadow-blue-200 mb-4 transform -rotate-6">M</div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">MANTRA</h1>
                <p class="text-slate-500 text-sm">Monitoring Analitik Notifikasi Terpadu Retribusi dan Pajak</p>
                <p class="text-slate-400 text-xs mt-1">Kabupaten Pasuruan</p>
            </div>

            {{-- Content --}}
            {{ $slot }}

            {{-- Footer --}}
            <p class="mt-10 text-center text-slate-400 text-xs">
                &copy; {{ date('Y') }} Dinas Komunikasi dan Informatika Kabupaten Pasuruan. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

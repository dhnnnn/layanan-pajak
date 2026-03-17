<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Page' }} — Sistem Realisasi Pajak Kab. Pasuruan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- Alpine.js for interactive components --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {{-- Tom Select for searchable dropdowns --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    
    <style>
        /* Tom Select Custom Styling to match Tailwind */
        .ts-wrapper.single .ts-control {
            background-color: white;
            border: 1px solid rgb(203 213 225);
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .ts-wrapper.single .ts-control:focus,
        .ts-wrapper.single.focus .ts-control {
            border-color: rgb(59 130 246);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .ts-wrapper .ts-dropdown {
            border: 1px solid rgb(203 213 225);
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            margin-top: 0.25rem;
        }
        
        .ts-dropdown .option {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .ts-dropdown .option.active {
            background-color: rgb(239 246 255);
            color: rgb(30 64 175);
        }
        
        .ts-dropdown .option:hover {
            background-color: rgb(243 244 246);
        }
        
        .ts-wrapper.single .ts-control .item {
            font-size: 0.875rem;
        }
        
        /* For error state */
        .ts-wrapper.error .ts-control {
            border-color: rgb(239 68 68);
        }
        
        /* For emerald focus (employee pages) */
        .ts-wrapper.emerald-focus.single .ts-control:focus,
        .ts-wrapper.emerald-focus.single.focus .ts-control {
            border-color: rgb(16 185 129);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
    </style>
    
    {{ $head ?? '' }}
</head>
<body class="bg-slate-100 font-sans antialiased">

<div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">

<div class="flex h-screen overflow-hidden">

    {{-- Sidebar Slot --}}
    {{ $sidebar ?? '' }}

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" 
                        class="lg:hidden p-2 -ml-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-base font-semibold text-slate-800">{{ $header ?? '' }}</h1>
            </div>
            <div class="flex items-center gap-3">{{ $headerActions ?? '' }}</div>
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

            {{ $slot }}

        </main>
    </div>

</div>

<script>
    // Initialize Tom Select on all select elements
    document.addEventListener('DOMContentLoaded', function() {
        const selectElements = document.querySelectorAll('select:not(.no-search)');
        
        selectElements.forEach(function(select) {
            if (select.tomselect) return;
            
            const hasOnChange = select.hasAttribute('onchange');
            const onChangeHandler = hasOnChange ? select.getAttribute('onchange') : null;
            const hasEmeraldFocus = select.classList.contains('focus:ring-emerald-500') || 
                                   select.classList.contains('focus:border-emerald-500');
            const hasError = select.classList.contains('border-red-500');
            
            new TomSelect(select, {
                create: false,
                sortField: { field: "text", direction: "asc" },
                placeholder: select.querySelector('option[disabled]')?.textContent || 'Pilih...',
                allowEmptyOption: true,
                onInitialize: function() {
                    if (hasEmeraldFocus) this.wrapper.classList.add('emerald-focus');
                    if (hasError) this.wrapper.classList.add('error');
                },
                onChange: function(value) {
                    if (onChangeHandler) eval(onChangeHandler);
                }
            });
        });
    });
</script>

</body>
</html>

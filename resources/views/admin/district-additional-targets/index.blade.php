<x-layouts.admin title="Target Tambahan Kecamatan" header="Data Target Tambahan Kecamatan">
    <x-slot:headerActions>
        <div class="flex items-center gap-2">
            {{-- Year filter --}}
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition-all">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span id="yearDropdownLabel">{{ $selectedYear }}</span>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1.5 w-32 bg-white border border-slate-200 rounded-xl shadow-lg py-1.5">
                    @foreach($availableYears as $y)
                        <a href="{{ route('admin.district-additional-targets.index', ['year' => $y]) }}"
                            class="block px-4 py-2 text-sm hover:bg-slate-50 transition-colors {{ $selectedYear == $y ? 'font-bold text-blue-600 bg-blue-50' : 'text-slate-700' }}">
                            {{ $y }}
                        </a>
                    @endforeach
                </div>
            </div>
        @can('manage additional-targets')
            <a href="{{ route('admin.district-additional-targets.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow-sm transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Target
            </a>
        @endcan
        </div>
    </x-slot:headerActions>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl px-4 py-3 flex items-center gap-2 mb-4">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Summary cards --}}
    @php
        $totalTambahan = $additionalTargets->sum('additional_target');
        $distinctDistricts = $additionalTargets->pluck('district_id')->unique()->count();
        $totalTargetTypes = $additionalTargets->count();
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center sm:text-left">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kecamatan Aktif</p>
            <p class="text-2xl font-black text-slate-800">{{ $distinctDistricts }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-4 text-center sm:text-left">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Target Tambahan</p>
            <p class="text-lg font-black text-amber-700">+Rp {{ number_format($totalTambahan, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-4 text-center sm:text-left">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Entri</p>
            <p class="text-2xl font-black text-blue-600">{{ $totalTargetTypes }}</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($additionalTargets->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase font-bold border-b-2 border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Kecamatan</th>
                        <th class="px-4 py-3 text-left">Jenis Pajak</th>
                        <th class="px-4 py-3 text-right">Target Awal</th>
                        <th class="px-4 py-3 text-right">Target Tambahan</th>
                        <th class="px-4 py-3 text-center">% Naik</th>
                        <th class="px-4 py-3 text-left min-w-[200px]">Distribusi per Tribulan</th>
                        <th class="px-4 py-3 text-left">Catatan</th>
                        @can('manage additional-targets')
                        <th class="px-4 py-3 text-center">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($additionalTargets as $at)
                        @php
                            $baseTarget = (float) ($baseTargetMap[$at->no_ayat] ?? 0);
                            $addTarget  = (float) $at->additional_target;
                            $pctNaik    = $baseTarget > 0 ? ($addTarget / $baseTarget) * 100 : 0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-slate-100 text-slate-700 text-[10px] font-bold rounded uppercase tracking-wider">
                                    {{ $at->district->name }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-bold text-slate-800">{{ $ayatLabels[$at->no_ayat] ?? $at->no_ayat }}</p>
                                <p class="text-xs font-mono text-slate-400">{{ $at->no_ayat }}</p>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-slate-500 text-xs text-nowrap">
                                Rp {{ number_format($baseTarget, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-amber-700 text-nowrap">
                                +Rp {{ number_format($addTarget, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">
                                    ↑ {{ number_format($pctNaik, 1, ',', '.') }}%
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1.5 flex-wrap">
                                    @foreach(['q1' => 'T1', 'q2' => 'T2', 'q3' => 'T3', 'q4' => 'T4'] as $col => $label)
                                        @php $val = (float) ($at->{$col . '_additional'} ?? 0); @endphp
                                        <span class="text-[9px] px-1.5 py-0.5 rounded font-mono {{ $val > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-50 text-slate-300 border border-slate-100' }}">
                                            {{ $label }}: {{ $val > 0 ? number_format($val, 0, ',', '.') : '—' }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs max-w-xs truncate">{{ $at->notes ?? '—' }}</td>
                            @can('manage additional-targets')
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <a href="{{ route('admin.district-additional-targets.create-specific', [$at->district, 'no_ayat' => $at->no_ayat, 'year' => $at->year]) }}"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.district-additional-targets.destroy', [$at->district, $at]) }}"
                                        onsubmit="return confirm('Hapus target tambahan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-16 text-center">
            <svg class="w-12 h-12 text-slate-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            </svg>
            <p class="text-slate-400 text-sm">Belum ada target tambahan kecamatan untuk tahun {{ $selectedYear }}.</p>
        </div>
        @endif
    </div>

    <script>
        document.getElementById('yearDropdownBtn').addEventListener('click', () => {
            document.getElementById('yearDropdownMenu').classList.toggle('hidden');
        });
        document.addEventListener('click', e => {
            if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                document.getElementById('yearDropdownMenu').classList.add('hidden');
            }
        });
    </script>
</x-layouts.admin>

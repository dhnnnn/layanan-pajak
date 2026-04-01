<x-layouts.admin title="Monitoring Realisasi" header="Monitoring Realisasi per UPT">
    <x-slot:headerActions>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.realization-monitoring.export-all', ['year' => $year]) }}" 
                class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-bold shadow-sm transition-all active:scale-95">
                <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Export Excel</span>
            </a>

            <form method="GET" action="{{ route('admin.realization-monitoring.index') }}" id="yearForm">
                <div class="relative" id="yearDropdownWrapper">
                    <button type="button" id="yearDropdownBtn"
                        class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 font-medium hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span id="yearDropdownLabel">{{ $year }}</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <input type="hidden" name="year" id="yearValue" value="{{ $year }}">
                    <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-36 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                        @foreach($availableYears as $y)
                            <button type="button" data-value="{{ $y }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ $year == $y ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                                {{ $y }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
    </x-slot:headerActions>

    {{-- Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Ketetapan (SPTPD) {{ $year }}</p>
            <p class="text-2xl font-black text-blue-600">Rp {{ number_format($totalSptpd, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Pembayaran {{ $year }}</p>
            <p class="text-2xl font-black text-emerald-600">Rp {{ number_format($totalPay, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Tunggakan {{ $year }}</p>
            <p class="text-2xl font-black text-rose-600">Rp {{ number_format($totalSptpd - $totalPay, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- UPT Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="px-6 py-4 text-center w-16">Rank</th>
                        <th class="px-6 py-4">UPT Bapenda</th>
                        <th class="px-6 py-4 text-center">Status Kinerja</th>
                        <th class="px-6 py-4 text-right">Ketetapan</th>
                        <th class="px-6 py-4 text-right">Realisasi</th>
                        <th class="px-6 py-4">Progress Bayar</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($upts as $index => $upt)
                        @php
                            $rank = $index + 1;
                            $pct = $upt->attainment_pct;
                            
                            $statusLabel = 'Kurang';
                            $statusColor = 'bg-rose-100 text-rose-700 border-rose-200';
                            if($pct >= 90) {
                                $statusLabel = 'Tercapai';
                                $statusColor = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                            } elseif($pct >= 50) {
                                $statusLabel = 'Hampir Tercapai';
                                $statusColor = 'bg-amber-100 text-amber-700 border-amber-200';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-6 py-4 text-center">
                                @if($rank == 1)
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 text-amber-600 font-black shadow-sm ring-1 ring-amber-200">1</span>
                                @elseif($rank == 2)
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 font-black shadow-sm ring-1 ring-slate-200">2</span>
                                @elseif($rank == 3)
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-50 text-orange-600 font-black shadow-sm ring-1 ring-orange-100">3</span>
                                @else
                                    <span class="text-slate-400 font-bold ml-2">{{ $rank }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $upt->name }}</div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-mono font-bold uppercase tracking-tight">{{ $upt->code }}</span>
                                    <span class="text-xs text-slate-400">{{ $upt->employees_count }} pegawai</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black border uppercase tracking-wider {{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-500">
                                Rp {{ number_format($upt->sptpd_total, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-600">
                                Rp {{ number_format($upt->pay_total, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 min-w-[200px]">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden ring-1 ring-slate-200">
                                        <div class="h-full rounded-full transition-all duration-1000 {{ $pct >= 90 ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]' : ($pct >= 50 ? 'bg-amber-400' : 'bg-rose-500') }}"
                                            style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-black {{ $pct >= 90 ? 'text-emerald-600' : ($pct >= 50 ? 'text-amber-600' : 'text-rose-600') }} w-12 text-right">
                                        {{ number_format($pct, 1) }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.realization-monitoring.show', [$upt, 'year' => $year]) }}"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-black text-white text-[11px] font-black rounded-lg transition-all shadow-md hover:shadow-lg active:scale-95 uppercase tracking-wider">
                                    <span>Detail</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 bg-slate-50 rounded-full">
                                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                        </svg>
                                    </div>
                                    <p class="text-slate-400 font-medium">Data realisasi belum tersedia untuk tahun {{ $year }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const btn = document.getElementById('yearDropdownBtn');
        const menu = document.getElementById('yearDropdownMenu');
        const yearValue = document.getElementById('yearValue');
        const yearLabel = document.getElementById('yearDropdownLabel');

        if (btn && menu) {
            btn.addEventListener('click', () => menu.classList.toggle('hidden'));

            document.addEventListener('click', function (e) {
                if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                    menu.classList.add('hidden');
                }
            });

            document.querySelectorAll('.year-option').forEach(function (opt) {
                opt.addEventListener('click', function () {
                    yearValue.value = this.dataset.value;
                    yearLabel.textContent = this.textContent.trim();
                    menu.classList.add('hidden');
                    document.getElementById('yearForm').submit();
                });
            });
        }
    </script>
</x-layouts.admin>

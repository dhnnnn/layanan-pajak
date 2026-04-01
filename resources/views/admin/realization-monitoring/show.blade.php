<x-layouts.admin :title="'Monitoring ' . $upt->name" :header="'Monitoring Realisasi: ' . $upt->name">
    <x-slot:headerActions>
        <form method="GET" action="{{ route('admin.realization-monitoring.show', $upt) }}" id="filterForm" class="flex items-center gap-2">
            {{-- Year Dropdown --}}
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 font-medium hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span id="yearDropdownLabel">{{ $year }}</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <input type="hidden" name="year" id="yearValue" value="{{ $year }}">
                <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-32 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                    @foreach($availableYears as $y)
                        <button type="button" data-value="{{ $y }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ $year == $y ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                            {{ $y }}
                        </button>
                    @endforeach
                </div>
            </div>
        </form>

        <a href="{{ route('admin.realization-monitoring.index', ['year' => $year]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    {{-- Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Petugas UPT</p>
            <p class="text-2xl font-black text-slate-900">{{ count($employeeData) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Ketetapan (SPTPD)</p>
            <p class="text-2xl font-black text-blue-600">Rp {{ number_format($uptSptpd, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Realisasi Bayar</p>
            <p class="text-2xl font-black text-emerald-600">Rp {{ number_format($uptPay, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Employee Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-700 font-bold uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="px-6 py-4 text-center w-16">Rank</th>
                        <th class="px-6 py-4">Petugas / Kolektor</th>
                        <th class="px-6 py-4 text-center">Status Kinerja</th>
                        <th class="px-6 py-4 text-right">Ketetapan</th>
                        <th class="px-6 py-4 text-right">Realisasi</th>
                        <th class="px-6 py-4">Achievement</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($employeeData as $index => $data)
                        @php 
                            $pct = $data['attainment_pct'];
                            $rank = $index + 1;
                            
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
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-4 text-center">
                                <span class="text-xs font-black {{ $rank <= 3 ? 'text-blue-600' : 'text-slate-400' }}">#{{ $rank }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-900 flex items-center justify-center shrink-0 shadow-lg group-hover:scale-110 transition-transform">
                                        <span class="text-xs font-black text-white">{{ strtoupper(substr($data['employee']->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $data['employee']->name }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">{{ $data['districts_count'] }} Kecamatan Tugas</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black border uppercase tracking-wider {{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-500">
                                Rp {{ number_format($data['sptpd_total'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-600">
                                Rp {{ number_format($data['pay_total'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 min-w-[180px]">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 bg-slate-100 rounded-full h-2 ring-1 ring-slate-100">
                                        <div class="h-full rounded-full transition-all duration-700 {{ $pct >= 90 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-amber-400' : 'bg-rose-500') }}"
                                            style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-black text-slate-600 w-12 text-right">{{ number_format($pct, 1) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.realization-monitoring.employee', [$upt, $data['employee'], 'year' => $year]) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-black rounded-lg transition-all active:scale-95 shadow-sm">
                                    Lihat WP
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                                Belum ada petugas terdaftar di UPT ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const yearBtn = document.getElementById('yearDropdownBtn');
        const yearMenu = document.getElementById('yearDropdownMenu');
        const yearValue = document.getElementById('yearValue');
        const yearLabel = document.getElementById('yearDropdownLabel');

        if (yearBtn && yearMenu) {
            yearBtn.addEventListener('click', () => yearMenu.classList.toggle('hidden'));

            document.addEventListener('click', function (e) {
                if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                    yearMenu.classList.add('hidden');
                }
            });

            document.querySelectorAll('.year-option').forEach(function (opt) {
                opt.addEventListener('click', function () {
                    yearValue.value = this.dataset.value;
                    yearLabel.textContent = this.textContent.trim();
                    yearMenu.classList.add('hidden');
                    document.getElementById('filterForm').submit();
                });
            });
        }
    </script>
</x-layouts.admin>

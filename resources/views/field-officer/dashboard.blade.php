<x-layouts.field-officer title="Dashboard Petugas Lapangan" header="Ringkasan Wilayah Tugas">
    <x-slot:headerActions>
        <form action="{{ route('field-officer.dashboard') }}" method="GET" id="filterForm" class="flex items-center gap-2">
            <input type="hidden" name="compliance_month" id="complianceMonthValue" value="{{ $complianceMonth }}">
            <span class="text-xs font-semibold text-slate-500 uppercase">Tahun:</span>
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span id="yearDropdownLabel">{{ $year }}</span>
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <input type="hidden" name="year" id="yearValue" value="{{ $year }}">
                <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-32 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                    @foreach($availableYears as $y)
                        <button type="button" data-value="{{ $y }}"
                            class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ $year == $y ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                            {{ $y }}
                        </button>
                    @endforeach
                </div>
            </div>
        </form>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- Info Bar --}}
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Wilayah Tugas</p>
                    <p class="text-sm font-bold text-slate-800">{{ $districts->pluck('name')->implode(', ') ?: 'Belum ada wilayah' }}</p>
                </div>
            </div>
        </div>

        {{-- Statistik --}}
        <h3 class="font-bold text-slate-800 text-sm uppercase tracking-widest pl-1 flex items-center gap-2">
            <div class="w-1.5 h-4 bg-amber-500 rounded-full"></div>
            Statistik
        </h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Total Ketetapan SPTPD</p>
                <p class="text-xl font-bold text-slate-900">Rp {{ number_format($summary['total_ketetapan'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Realisasi Bayar</p>
                <p class="text-xl font-bold text-blue-600">Rp {{ number_format($summary['total_bayar'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Sisa Tunggakan</p>
                <p class="text-xl font-bold text-orange-600">Rp {{ number_format($summary['total_tunggakan'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Persentase Capaian</p>
                <p class="text-xl font-black {{ $summary['persentase'] >= 100 ? 'text-emerald-600' : ($summary['persentase'] >= 50 ? 'text-amber-500' : 'text-rose-600') }}">
                    {{ number_format($summary['persentase'], 2, ',', '.') }}%
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Kepatuhan Pelaporan --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-slate-800 text-xs uppercase tracking-widest">Kepatuhan Pelaporan</h3>
                        @php $bulanIndo = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; @endphp
                        <p class="text-[10px] text-slate-400 mt-0.5">Bulan: <span class="font-bold text-slate-600">{{ $bulanIndo[$compliance['month']] }} {{ $year }}</span></p>
                    </div>
                    <form action="{{ route('field-officer.dashboard') }}" method="GET" id="complianceForm">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="compliance_month" id="complianceMonthInput" value="{{ $complianceMonth }}">
                        <div class="w-32 relative" x-data='{
                            open: false,
                            value: "{{ $complianceMonth }}",
                            options: [
                                {id:"1",name:"Januari"},{id:"2",name:"Februari"},{id:"3",name:"Maret"},
                                {id:"4",name:"April"},{id:"5",name:"Mei"},{id:"6",name:"Juni"},
                                {id:"7",name:"Juli"},{id:"8",name:"Agustus"},{id:"9",name:"September"},
                                {id:"10",name:"Oktober"},{id:"11",name:"November"},{id:"12",name:"Desember"}
                            ],
                            get label() { return this.options.find(o => o.id === String(this.value))?.name ?? "Pilih"; },
                            select(opt) {
                                this.value = opt.id; this.open = false;
                                document.getElementById("complianceMonthInput").value = opt.id;
                                document.getElementById("complianceForm").submit();
                            }
                        }'>
                            <button type="button" @click="open = !open" @click.away="open = false"
                                class="w-full flex items-center justify-between px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 hover:border-blue-400 transition-all">
                                <span x-text="label" class="font-bold text-slate-900 truncate"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                class="absolute right-0 z-50 mt-2 w-full bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden" style="display:none;">
                                <div class="py-1 max-h-60 overflow-y-auto">
                                    <template x-for="opt in options" :key="opt.id">
                                        <button type="button" @click="select(opt)"
                                            class="w-full text-left px-4 py-2 text-xs hover:bg-slate-50 transition-colors"
                                            :class="String(value) === opt.id ? 'text-blue-600 font-bold bg-blue-50/30' : 'text-slate-600'">
                                            <div class="flex items-center justify-between">
                                                <span x-text="opt.name"></span>
                                                <svg x-show="String(value) === opt.id" class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-center items-center">
                    <div class="relative w-32 h-32 mb-4">
                        <svg class="w-full h-full" viewBox="0 0 36 36">
                            <path class="text-slate-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <path class="text-amber-500" stroke-width="3" stroke-dasharray="{{ $compliance['percentage'] }}, 100" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold text-slate-900">{{ round($compliance['percentage']) }}%</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 font-medium text-center">
                        <span class="font-bold text-amber-600">{{ $compliance['reported'] }}</span> WP Sudah Lapor dari total <span class="font-bold text-slate-800">{{ $compliance['total'] }}</span>
                    </p>
                </div>
            </div>

            {{-- Prioritas Penagihan --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 text-xs uppercase tracking-widest">Prioritas Penagihan (Top 5)</h3>
                    <span class="px-2 py-1 bg-red-100 text-red-700 text-[10px] font-bold rounded uppercase">Tindak Lanjut</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($topDelinquents as $dp)
                    <div class="px-6 py-3 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-800 uppercase truncate">{{ $dp->nm_wp }}</p>
                            <p class="text-[9px] text-slate-400 uppercase truncate">{{ $dp->nm_op }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-black text-rose-600">Rp {{ number_format($dp->debt, 0, ',', '.') }}</p>
                            <a href="{{ route('admin.monitoring.index', ['search' => $dp->npwpd]) }}"
                                class="text-[9px] text-blue-500 hover:underline font-bold">Pantau</a>
                        </div>
                    </div>
                    @empty
                    <div class="px-6 py-10 text-center text-slate-400 italic text-xs">Tidak ada tunggakan.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Kecamatan yang Diampu --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30">
                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-widest">Kecamatan yang Diampu</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3">Kecamatan</th>
                            <th class="px-6 py-3 text-right">Total WP</th>
                            <th class="px-6 py-3 text-right">Ketetapan</th>
                            <th class="px-6 py-3 text-right">Realisasi</th>
                            <th class="px-6 py-3 text-right">Tunggakan</th>
                            <th class="px-6 py-3 text-center">Capaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($districts as $district)
                            @php
                                $s = \Illuminate\Support\Facades\DB::table('simpadu_tax_payers')
                                    ->where('year', $year)->where('status', '1')
                                    ->where('kd_kecamatan', $district->simpadu_code)
                                    ->selectRaw('COUNT(*) as n, COALESCE(SUM(total_ketetapan),0) as k, COALESCE(SUM(total_bayar),0) as b, COALESCE(SUM(total_tunggakan),0) as t')
                                    ->first();
                                $pct = $s && $s->k > 0 ? ($s->b / $s->k) * 100 : 0;
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3 font-bold text-slate-800">{{ $district->name }}</td>
                                <td class="px-6 py-3 text-right text-slate-600">{{ number_format($s->n ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right text-slate-600">Rp {{ number_format($s->k ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right text-emerald-600 font-bold">Rp {{ number_format($s->b ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right text-orange-600 font-bold">Rp {{ number_format($s->t ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black {{ $pct >= 100 ? 'bg-emerald-100 text-emerald-700' : ($pct >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                        {{ number_format($pct, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400 italic">Belum ada kecamatan yang diampu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('yearDropdownBtn').addEventListener('click', () => {
            document.getElementById('yearDropdownMenu').classList.toggle('hidden');
        });
        document.addEventListener('click', function(e) {
            if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                document.getElementById('yearDropdownMenu').classList.add('hidden');
            }
        });
        document.querySelectorAll('.year-option').forEach(opt => {
            opt.addEventListener('click', function() {
                document.getElementById('yearValue').value = this.dataset.value;
                document.getElementById('yearDropdownLabel').textContent = this.textContent.trim();
                document.getElementById('yearDropdownMenu').classList.add('hidden');
                document.getElementById('filterForm').submit();
            });
        });
    </script>
</x-layouts.field-officer>

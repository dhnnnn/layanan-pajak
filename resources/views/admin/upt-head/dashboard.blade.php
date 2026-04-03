<x-layouts.admin title="Dashboard UPT" header="Ringkasan UPT">
    <x-slot:headerActions>
        <form action="{{ route('admin.dashboard') }}" method="GET" id="filterForm" class="flex items-center gap-2">
            <input type="hidden" name="district_id" value="{{ $isAllDistricts ? 'all' : $selectedDistrictId }}">
            <span class="text-xs font-semibold text-slate-500 uppercase">Tahun:</span>
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span id="yearDropdownLabel">{{ $selectedYear }}</span>
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <input type="hidden" name="year" id="yearValue" value="{{ $selectedYear }}">
                <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-32 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                    @forelse($availableYears as $y)
                        <button type="button" data-value="{{ $y }}"
                            class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ $selectedYear == $y ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                            {{ $y }}
                        </button>
                    @empty
                        <button type="button" data-value="{{ date('Y') }}" class="year-option w-full text-left px-4 py-2 text-sm text-slate-700">
                            {{ date('Y') }}
                        </button>
                    @endforelse
                </div>
            </div>
        </form>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- UPT Info --}}
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Unit Pelaksana Teknis</p>
                    <p class="text-sm font-bold text-slate-800">{{ auth()->user()->upt?->name ?? 'UPT' }}</p>
                </div>
            </div>
        </div>

        {{-- Statistik Utama Section (REVERTED TO BIG CARDS) --}}
        <h3 class="font-bold text-slate-800 text-sm uppercase tracking-widest pl-1 mb-4 flex items-center gap-2">
            <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
            Statistik
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $totalTarget = $totals['target'] ?? 0;
                $totalRealization = $totals['realization'] ?? 0;
                $avgPercentage = $totals['percentage'] ?? 0;
                $remainingTarget = max(0, $totalTarget - $totalRealization);
            @endphp

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Total Ketetapan SPTPD</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Realisasi Bayar</p>
                <p class="text-2xl font-bold text-blue-600">Rp {{ number_format($totalRealization, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Sisa</p>
                <p class="text-2xl font-bold text-orange-600">Rp {{ number_format($remainingTarget, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider mb-1">Persentase Capaian</p>
                <p class="text-2xl font-black text-emerald-600">{{ number_format($avgPercentage, 2, ',', '.') }}%</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Kepatuhan Pelaporan Section --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-slate-800 text-xs uppercase tracking-widest">Kepatuhan Pelaporan</h3>
                        @if($compliance)
                        @php
                            $bulanIndo = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        @endphp
                        <p class="text-[10px] text-slate-400 mt-0.5">Bulan: <span class="font-bold text-slate-600">{{ $bulanIndo[$compliance['month']] }} {{ $selectedYear }}</span></p>
                        @endif
                    </div>
                    {{-- Dropdown bulan --}}
                    <form action="{{ route('admin.dashboard') }}" method="GET" id="complianceMonthForm">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="district_id" value="{{ $isAllDistricts ? 'all' : $selectedDistrictId }}">
                        <input type="hidden" name="priority_district_id" value="{{ request('priority_district_id') }}">
                        <input type="hidden" name="compliance_month" id="complianceMonthValue" value="{{ $compliance['month'] ?? date('n') }}">
                        <div class="w-36 relative" x-data='{
                            open: false,
                            value: "{{ $compliance["month"] ?? date("n") }}",
                            options: [
                                {id:"1",name:"Januari"},{id:"2",name:"Februari"},{id:"3",name:"Maret"},
                                {id:"4",name:"April"},{id:"5",name:"Mei"},{id:"6",name:"Juni"},
                                {id:"7",name:"Juli"},{id:"8",name:"Agustus"},{id:"9",name:"September"},
                                {id:"10",name:"Oktober"},{id:"11",name:"November"},{id:"12",name:"Desember"}
                            ],
                            get label() {
                                return this.options.find(o => o.id === String(this.value))?.name ?? "Pilih Bulan";
                            },
                            select(opt) {
                                this.value = opt.id;
                                this.open = false;
                                document.getElementById("complianceMonthValue").value = opt.id;
                                document.getElementById("complianceMonthForm").submit();
                            }
                        }'>
                            <button type="button" @click="open = !open" @click.away="open = false"
                                class="w-full flex items-center justify-between px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 hover:border-blue-400 focus:outline-none transition-all">
                                <span x-text="label" class="font-bold text-slate-900 truncate"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 z-50 mt-2 w-full bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden"
                                style="display:none;">
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
                    @if($compliance)
                    <div class="relative w-32 h-32 mb-4">
                        <svg class="w-full h-full" viewBox="0 0 36 36">
                            <path class="text-slate-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-blue-600" stroke-width="3" stroke-dasharray="{{ $compliance['percentage'] }}, 100" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold text-slate-900">{{ round($compliance['percentage']) }}%</span>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-500 font-medium">
                            <span class="font-bold text-blue-600">{{ $compliance['reported'] }}</span> WP Sudah Lapor dari total <span class="font-bold text-slate-800">{{ $compliance['total'] }}</span>
                        </p>
                    </div>
                    @else
                    <p class="text-slate-400 italic text-sm">Data tidak tersedia</p>
                    @endif
                </div>
            </div>

            {{-- Kinerja Petugas (REVERTED TO LIST BARS) --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30">
                    <h3 class="font-bold text-slate-800 text-xs uppercase tracking-widest">Kinerja Petugas (Top 5)</h3>
                </div>
                <div class="p-5 space-y-5">
                    @forelse($employeeDashboardData as $data)
                    <div class="space-y-2">
                        <div class="flex justify-between items-end">
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold text-slate-700 uppercase tracking-tight truncate">{{ $data['employee']->name }}</p>
                                <p class="text-[10px] text-slate-400">
                                    Rp{{ number_format($data['pay_total'], 0, ',', '.') }} / 
                                    Rp{{ number_format($data['sptpd_total'], 0, ',', '.') }}
                                </p>
                            </div>
                            <span class="text-[11px] font-black text-blue-600">{{ number_format($data['attainment_pct'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $data['attainment_pct'] }}%"></div>
                        </div>
                        <div class="flex justify-between text-[9px] text-slate-400 italic">
                            <span>Sisa: Rp{{ number_format($data['remaining'], 0, ',', '.') }}</span>
                            <span>{{ $data['districts_count'] }} Wilayah</span>
                        </div>
                    </div>
                    @empty
                    <p class="text-slate-400 italic text-xs py-4 text-center">Belum ada data kinerja petugas.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Prioritas Penagihan Section --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-800 text-xs uppercase tracking-widest">Prioritas Penagihan (Tunggakan Terbesar)</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">
                        Wilayah: <span class="font-bold text-slate-600">{{ $selectedPriorityDistrict ? $selectedPriorityDistrict->name : 'Semua Wilayah' }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Filter Wilayah khusus prioritas pakai searchable-select --}}
                    <form action="{{ route('admin.dashboard') }}" method="GET" id="priorityFilterForm">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="district_id" value="{{ $isAllDistricts ? 'all' : $selectedDistrictId }}">
                        <input type="hidden" name="priority_district_id" id="priorityDistrictHidden" value="{{ $priorityDistrictId ?? '' }}">
                        <div class="w-48">
                            <x-searchable-select
                                target-input-id="priorityDistrictHidden"
                                :value="$priorityDistrictId ?? ''"
                                placeholder="Semua Wilayah"
                                :options="$assignedDistricts->map(fn($d) => ['id' => $d->id, 'name' => $d->name])->toArray()"
                            />
                        </div>
                    </form>
                    <span class="px-2 py-1 bg-red-100 text-red-700 text-[10px] font-bold rounded uppercase">Tindak Lanjut Segera</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3">Wajib Pajak</th>
                            <th class="px-6 py-3 text-right">Target</th>
                            <th class="px-6 py-3 text-right">Realisasi</th>
                            <th class="px-6 py-3 text-right">Tunggakan</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topDelinquents as $dp)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3">
                                <div class="font-bold text-slate-800">{{ $dp->nm_wp }}</div>
                                <div class="text-[10px] text-slate-400 uppercase tracking-tight">{{ $dp->nm_op }}</div>
                            </td>
                            <td class="px-6 py-3 text-right font-medium">Rp {{ number_format($dp->target, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-right font-medium text-emerald-600">Rp {{ number_format($dp->realization, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-right font-bold text-red-600">Rp {{ number_format($dp->debt, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-center">
                                <a href="{{ route('admin.monitoring.index', ['search' => $dp->npwpd]) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-blue-600 hover:text-white text-slate-600 font-bold rounded-lg transition-all">
                                    Pantau
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400 italic">Tidak ada data tunggakan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Year dropdown
        document.getElementById('yearDropdownBtn').addEventListener('click', () => {
            document.getElementById('yearDropdownMenu').classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                document.getElementById('yearDropdownMenu').classList.add('hidden');
            }
        });

        document.querySelectorAll('.year-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                document.getElementById('yearValue').value = this.dataset.value;
                document.getElementById('yearDropdownLabel').textContent = this.textContent.trim();
                document.getElementById('yearDropdownMenu').classList.add('hidden');
                document.getElementById('filterForm').submit();
            });
        });

        // searchable-select di prioritas penagihan dispatch submit event ke form
        document.getElementById('priorityFilterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
    </script>
</x-layouts.admin>

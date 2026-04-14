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
                    <p class="text-sm font-bold text-slate-800">{{ auth()->user()->upt()?->name ?? 'UPT' }}</p>
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

        {{-- Grafik Prediksi Pendapatan --}}
        @if($upt)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div class="w-1 h-5 bg-orange-400 rounded-full"></div>
                    <h3 class="text-sm font-semibold text-slate-800">Prediksi Pendapatan per Kecamatan</h3>
                </div>
                <div class="relative" id="dashDistrictDropdownWrapper">
                    <button type="button" id="dashDistrictDropdownBtn"
                        class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 font-bold hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span id="dashDistrictDropdownLabel">Semua Kecamatan</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="dashDistrictDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-52 bg-white border border-slate-200 rounded-xl shadow-xl py-1 max-h-64 overflow-y-auto">
                        <button type="button" data-id="all" data-label="Semua Kecamatan"
                            class="dash-district-opt w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 font-black text-blue-600 bg-blue-50/50">
                            Semua Kecamatan
                        </button>
                        @foreach($assignedDistricts->sortBy('name') as $district)
                            <button type="button" data-id="{{ $district->id }}" data-label="{{ $district->name }}"
                                class="dash-district-opt w-full text-left px-4 py-2.5 text-xs hover:bg-slate-50 text-slate-700 font-bold">
                                {{ $district->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div id="dashForecastLoading" class="hidden px-6 py-12 text-center">
                <div class="flex flex-col items-center justify-center gap-3">
                    <img src="{{ asset('img/generating_report.gif') }}" alt="Memuat..." class="w-24 h-24 object-contain">
                    <p class="text-xs text-slate-400 font-medium">Sedang memproses prediksi...</p>
                </div>
            </div>

            <div id="dashForecastError" class="hidden px-6 py-8 text-center">
                <p class="text-rose-500 text-sm font-bold" id="dashForecastErrorMsg">Gagal memuat prediksi.</p>
            </div>

            <div id="dashForecastChartArea" class="hidden p-5">
                <div class="flex items-start justify-between gap-3 flex-wrap mb-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-800" id="dashForecastTitle">—</p>
                        <p class="text-xs text-slate-400 mt-0.5" id="dashForecastSubtitle"></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 shrink-0">
                        <span class="flex items-center gap-1.5"><span class="inline-block w-6 h-0.5 bg-blue-500"></span> Ketetapan</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block w-6 h-0.5 bg-emerald-500"></span> Realisasi</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block w-6 border-t-2 border-dashed border-orange-400"></span> Prediksi</span>
                        <button onclick="document.getElementById('dashForecastInfoModal').classList.remove('hidden')"
                            class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 transition-colors"
                            title="Informasi tentang prediksi ini">
                            <span class="text-[10px] font-black leading-none">i</span>
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-full sm:w-auto mb-4">
                    <button data-range="year" class="dash-range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors bg-white text-blue-600 shadow-sm text-center">Tahun Ini</button>
                    <button data-range="1y"   class="dash-range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700 text-center">1 Thn Terakhir</button>
                    <button data-range="2y"   class="dash-range-btn flex-1 sm:flex-none px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-slate-500 hover:text-slate-700 text-center">2 Thn Terakhir</button>
                </div>
                <div class="flex flex-wrap items-center gap-4 mb-4">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Model:</span>
                        <span id="dashForecastModel" class="text-[10px] font-bold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full border border-orange-200"></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">sMAPE:</span>
                        <span id="dashForecastMape" class="text-[10px] font-bold"></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">MAE:</span>
                        <span id="dashForecastMae" class="text-[10px] font-bold text-slate-700"></span>
                    </div>
                </div>
                <div id="dashForecastChartWrapper" class="w-full"><canvas id="dashForecastChart"></canvas></div>
            </div>

            {{-- Tombol Lihat Detail --}}
            <div class="px-5 pb-5 flex justify-end">
                @if($upt)
                <a href="{{ route('admin.employees.index', ['year' => $selectedYear]) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-black text-white text-xs font-black rounded-lg transition-all shadow-md hover:shadow-lg active:scale-95 uppercase tracking-wider">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Lihat Detail Kinerja Petugas
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @endif
            </div>
        </div>
        @endif

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
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold text-slate-700 uppercase tracking-tight truncate">{{ $data['employee']->name }}</p>
                                <p class="text-[10px] text-slate-400">
                                    Rp{{ number_format($data['pay_total'], 0, ',', '.') }} / 
                                    Rp{{ number_format($data['sptpd_total'], 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-[11px] font-black text-blue-600">{{ number_format($data['attainment_pct'], 1) }}%</span>
                                @if($upt)
                                <a href="{{ route('admin.realization-monitoring.employee', [$upt, $data['employee'], 'year' => $selectedYear]) }}"
                                    class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 hover:bg-blue-600 hover:text-white text-slate-600 text-[9px] font-black uppercase tracking-wider rounded-lg transition-all">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Cek
                                </a>
                                @endif
                            </div>
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

    @if($upt)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const forecastUrl   = '{{ route('admin.realization-monitoring.district-forecast', $upt) }}';
        const selectedYear  = {{ $selectedYear }};
        const CACHE_VERSION = 'v3';
        let chartInstance   = null;
        let currentRange    = 'year';
        let lastRawData     = null;
        let resizeTimer     = null;

        const elLoading   = document.getElementById('dashForecastLoading');
        const elError     = document.getElementById('dashForecastError');
        const elErrorMsg  = document.getElementById('dashForecastErrorMsg');
        const elChartArea = document.getElementById('dashForecastChartArea');
        const distMenu    = document.getElementById('dashDistrictDropdownMenu');
        const distLbl     = document.getElementById('dashDistrictDropdownLabel');

        const fmtFull    = v => 'Rp ' + Math.round(v).toLocaleString('id-ID');
        const fmtCompact = v => {
            if (v >= 1e9) return 'Rp ' + (v / 1e9).toFixed(1) + ' M';
            if (v >= 1e6) return 'Rp ' + (v / 1e6).toFixed(1) + ' Jt';
            return 'Rp ' + v.toLocaleString('id-ID');
        };
        const fmtPeriode = p => {
            const [y, m] = p.split('-');
            return ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][+m - 1] + ' ' + y;
        };

        document.getElementById('dashDistrictDropdownBtn').addEventListener('click', () => distMenu.classList.toggle('hidden'));
        document.addEventListener('click', e => {
            if (!document.getElementById('dashDistrictDropdownWrapper').contains(e.target)) distMenu.classList.add('hidden');
        });

        function showState(state) {
            [elLoading, elError, elChartArea].forEach(el => el.classList.add('hidden'));
            if (state === 'loading') elLoading.classList.remove('hidden');
            if (state === 'error')   elError.classList.remove('hidden');
            if (state === 'chart')   elChartArea.classList.remove('hidden');
        }

        function filterByRange(arr, range) {
            if (range === 'year') return arr.filter(h => h.periode >= `${selectedYear}-01` && h.periode <= `${selectedYear}-12`);
            if (range === '1y')   return arr.filter(h => h.periode >= `${selectedYear - 1}-01` && h.periode <= `${selectedYear}-12`);
            return arr.filter(h => h.periode >= `${selectedYear - 2}-01` && h.periode <= `${selectedYear}-12`);
        }

        function setRange(range) {
            currentRange = range;
            document.querySelectorAll('.dash-range-btn').forEach(btn => {
                const active = btn.dataset.range === range;
                btn.classList.toggle('bg-white', active);
                btn.classList.toggle('text-blue-600', active);
                btn.classList.toggle('shadow-sm', active);
                btn.classList.toggle('text-slate-500', !active);
                btn.classList.toggle('hover:text-slate-700', !active);
            });
            if (lastRawData) renderChart(lastRawData);
        }

        document.querySelectorAll('.dash-range-btn').forEach(btn => {
            btn.addEventListener('click', () => setRange(btn.dataset.range));
        });

        function renderChart(data) {
            lastRawData = data;

            const filteredH = filterByRange(data.historis ?? [], currentRange).filter(h => h.nilai > 0);
            const forecast  = data.forecast ?? [];
            const fitted    = data.fitted ?? [];
            const ketetapan = filterByRange(data.total_ketetapan ?? [], currentRange).filter(k => k.nilai > 0);

            const hMap      = Object.fromEntries(filteredH.map(h => [h.periode, h.nilai]));
            const fMap      = Object.fromEntries(forecast.map(f => [f.periode, f.nilai]));
            const fittedMap = Object.fromEntries(fitted.map(f => [f.periode, f.nilai]));
            const kMap      = Object.fromEntries(ketetapan.map(k => [k.periode, k.nilai]));

            const lastHPeriode = filteredH.length > 0 ? filteredH[filteredH.length - 1].periode : null;
            const allValidH    = (data.historis ?? []).filter(h => h.nilai > 0);
            const globalLastH  = allValidH.length > 0 ? allValidH[allValidH.length - 1].periode : null;
            const showForecast = lastHPeriode && globalLastH && lastHPeriode === globalLastH;
            const forecastVisible = showForecast ? forecast.filter(f => f.periode > lastHPeriode) : [];

            const allPeriods = [...new Set([
                ...filteredH.map(h => h.periode),
                ...forecastVisible.map(f => f.periode),
            ])].sort();

            const labels = allPeriods.map(p => fmtPeriode(p));
            const fittedCutoff = `${selectedYear - 2}-01`;

            const predDataset = allPeriods.map(p => {
                if (fMap[p] !== undefined && p > (lastHPeriode ?? '')) return fMap[p];
                if (p === lastHPeriode) return fittedMap[p] ?? hMap[p];
                if (hMap[p] !== undefined && fittedMap[p] !== undefined) return fittedMap[p];
                return null;
            });

            // Bridge: isi gap antara akhir historis dan awal forecast agar garis tidak putus
            if (lastHPeriode && forecastVisible.length > 0) {
                const lastHIdx  = allPeriods.indexOf(lastHPeriode);
                const firstFIdx = allPeriods.findIndex(p => fMap[p] !== undefined);
                if (firstFIdx > lastHIdx + 1) {
                    const startVal = predDataset[lastHIdx] ?? hMap[lastHPeriode];
                    const endVal   = predDataset[firstFIdx];
                    const steps    = firstFIdx - lastHIdx;
                    for (let i = 1; i < steps; i++) {
                        predDataset[lastHIdx + i] = startVal + (endVal - startVal) * (i / steps);
                    }
                }
            }

            const wrapper = document.getElementById('dashForecastChartWrapper');
            if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
            wrapper.innerHTML = '<canvas id="dashForecastChart"></canvas>';

            chartInstance = new Chart(document.getElementById('dashForecastChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { label: 'Ketetapan',       data: allPeriods.map(p => kMap[p] ?? null), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.06)', borderWidth: 2, pointRadius: 2, tension: 0.3, fill: true, spanGaps: false },
                        { label: 'Realisasi Aktual', data: allPeriods.map(p => hMap[p] ?? null), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.07)', borderWidth: 2, pointRadius: 2, tension: 0.3, fill: true, spanGaps: false },
                        { label: 'Prediksi Pendapatan',         data: predDataset, borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,0.06)', borderWidth: 2, borderDash: [6, 4], pointRadius: 3, tension: 0.3, fill: true, spanGaps: false },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => ctx.parsed.y !== null ? `${ctx.dataset.label}: ${fmtFull(ctx.parsed.y)}` : null } },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 18 } },
                        y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 }, callback: v => fmtCompact(v) } },
                    },
                },
            });

            const kec = data.kecamatan ?? '';
            const firstP = filteredH[0]?.periode ?? '';
            const lastFP = forecastVisible.length > 0 ? forecastVisible[forecastVisible.length - 1].periode : (lastHPeriode ?? '');
            document.getElementById('dashForecastTitle').textContent    = `Prediksi Realisasi Pendapatan${kec ? ' — ' + kec : ''}`;
            document.getElementById('dashForecastSubtitle').textContent = `${firstP ? fmtPeriode(firstP) : ''} s/d ${lastFP ? fmtPeriode(lastFP) : ''} · ${filteredH.length} bulan historis · garis oranye = prediksi realisasi`;
            document.getElementById('dashForecastModel').textContent    = data.model_used ?? '-';

            const mape   = data.mape ?? 0;
            const mapeEl = document.getElementById('dashForecastMape');
            mapeEl.textContent = mape.toFixed(2) + '%';
            mapeEl.className   = 'text-[10px] font-bold ' + (mape < 20 ? 'text-emerald-600' : mape < 40 ? 'text-amber-500' : 'text-rose-600');
            document.getElementById('dashForecastMae').textContent = fmtFull(Math.round(data.mae ?? 0));
        }

        async function loadForecast(districtId) {
            const cacheKey = `forecast_${CACHE_VERSION}_{{ $upt->id }}_${districtId}_{{ $selectedYear }}`;
            const cached   = sessionStorage.getItem(cacheKey);
            if (cached) {
                try { renderChart(JSON.parse(cached)); showState('chart'); return; }
                catch (e) { sessionStorage.removeItem(cacheKey); }
            }
            showState('loading');
            try {
                const res = await fetch(`${forecastUrl}?district_id=${districtId}`);
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    elErrorMsg.textContent = err.error ?? `Error ${res.status}`;
                    showState('error'); return;
                }
                const data = await res.json();
                sessionStorage.setItem(cacheKey, JSON.stringify(data));
                renderChart(data); showState('chart');
            } catch (e) {
                elErrorMsg.textContent = 'Forecasting service tidak dapat dijangkau.';
                showState('error');
            }
        }

        document.querySelectorAll('.dash-district-opt').forEach(opt => {
            opt.addEventListener('click', function () {
                document.querySelectorAll('.dash-district-opt').forEach(o => {
                    o.classList.remove('font-black', 'text-blue-600', 'bg-blue-50/50');
                    o.classList.add('text-slate-700', 'font-bold');
                });
                this.classList.add('font-black', 'text-blue-600', 'bg-blue-50/50');
                this.classList.remove('text-slate-700', 'font-bold');
                distLbl.textContent = this.dataset.label;
                distMenu.classList.add('hidden');
                loadForecast(this.dataset.id);
            });
        });

        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => { if (lastRawData) renderChart(lastRawData); }, 300);
        });

        loadForecast('all');
    })();
    </script>

    {{-- Modal Info Prediksi --}}
    <div id="dashForecastInfoModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('dashForecastInfoModal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 z-10">
            <div class="flex items-start justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-orange-100 rounded-xl flex items-center justify-center shrink-0">
                        <span class="text-orange-500 font-black text-base leading-none">i</span>
                    </div>
                    <h3 class="text-sm font-black text-slate-800">Dari Mana Data Prediksi Ini Berasal?</h3>
                </div>
                <button onclick="document.getElementById('dashForecastInfoModal').classList.add('hidden')"
                    class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="space-y-3 text-sm text-slate-600">
                <p class="text-xs text-slate-500 leading-relaxed">Prediksi dihitung menggunakan model statistik SARIMA/ARIMA berdasarkan data historis realisasi pajak per kecamatan. Semakin lengkap data historis, semakin akurat prediksi.</p>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex gap-2.5">
                    <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-xs text-amber-700 leading-relaxed">sMAPE di bawah 20% = sangat akurat. 20–40% = cukup baik. Di atas 40% = data historis terbatas.</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-layouts.admin>

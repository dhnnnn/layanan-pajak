<x-layouts.admin title="Dashboard UPT" header="Ringkasan UPT">
    <x-slot:headerActions>
        <form action="{{ route('admin.dashboard') }}" method="GET" id="filterForm" class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-500 uppercase">Wilayah:</span>
            <div class="relative" id="districtDropdownWrapper">
                <button type="button" id="districtDropdownBtn"
                    class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span id="districtDropdownLabel">
                        {{ $isAllDistricts ? 'Semua Wilayah' : ($selectedDistrictId ? $assignedDistricts->firstWhere('id', $selectedDistrictId)?->name : 'Pilih Wilayah') }}
                    </span>
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <input type="hidden" name="district_id" id="districtValue" value="{{ $isAllDistricts ? 'all' : $selectedDistrictId }}">
                <div id="districtDropdownMenu" class="hidden absolute right-0 z-20 mt-1 min-w-max bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                    <button type="button" data-value="all" data-label="Semua Wilayah"
                        class="district-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ $isAllDistricts ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                        Semua Wilayah
                    </button>
                    @foreach($assignedDistricts as $district)
                        <button type="button" data-value="{{ $district->id }}" data-label="{{ $district->name }}"
                            class="district-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ !$isAllDistricts && $selectedDistrictId == $district->id ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                            {{ $district->name }}
                        </button>
                    @endforeach
                </div>
            </div>

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
            <div class="text-right text-[10px]">
                <p class="text-slate-400 font-bold uppercase tracking-wider">Filter Aktif</p>
                <p class="font-bold text-slate-800">{{ $isAllDistricts ? 'Semua Wilayah' : $assignedDistricts->firstWhere('id', $selectedDistrictId)?->name }} ({{ $selectedYear }})</p>
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
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30">
                    <h3 class="font-bold text-slate-800 text-xs uppercase tracking-widest">Kepatuhan Pelaporan Bulan Ini</h3>
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
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-widest">Prioritas Penagihan (Tunggakan Terbesar)</h3>
                <span class="px-2 py-1 bg-red-100 text-red-700 text-[10px] font-bold rounded uppercase">Tindak Lanjut Segera</span>
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
        // District dropdown
        document.getElementById('districtDropdownBtn').addEventListener('click', () => {
            document.getElementById('districtDropdownMenu').classList.toggle('hidden');
        });

        // Year dropdown
        document.getElementById('yearDropdownBtn').addEventListener('click', () => {
            document.getElementById('yearDropdownMenu').classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!document.getElementById('districtDropdownWrapper').contains(e.target)) {
                document.getElementById('districtDropdownMenu').classList.add('hidden');
            }
            if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                document.getElementById('yearDropdownMenu').classList.add('hidden');
            }
        });

        document.querySelectorAll('.district-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                document.getElementById('districtValue').value = this.dataset.value;
                document.getElementById('districtDropdownLabel').textContent = this.dataset.label;
                document.getElementById('districtDropdownMenu').classList.add('hidden');
                document.getElementById('filterForm').submit();
            });
        });

        document.querySelectorAll('.year-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                document.getElementById('yearValue').value = this.dataset.value;
                document.getElementById('yearDropdownLabel').textContent = this.textContent.trim();
                document.getElementById('yearDropdownMenu').classList.add('hidden');
                document.getElementById('filterForm').submit();
            });
        });
    </script>
</x-layouts.admin>

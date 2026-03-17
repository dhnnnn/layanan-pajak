<x-layouts.employee title="Dashboard Pegawai" header="Dashboard Realisasi Pajak">
    <x-slot:headerActions>
        <form action="{{ route('pegawai.dashboard') }}" method="GET" class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <label for="district_id" class="text-xs font-semibold text-slate-500 uppercase">Wilayah:</label>
                <select name="district_id" id="district_id" onchange="this.form.submit()" class="text-sm rounded-lg bg-slate-50 text-slate-700 py-1.5 px-3 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 block">
                    <option value="">Semua Wilayah Saya</option>
                    @foreach($assignedDistricts as $district)
                        <option value="{{ $district->id }}" {{ $selectedDistrictId == $district->id ? 'selected' : '' }}>{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label for="year" class="text-xs font-semibold text-slate-500 uppercase">Tahun:</label>
                <select name="year" id="year" onchange="this.form.submit()" class="text-sm rounded-lg bg-slate-50 text-slate-700 py-1.5 px-3 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 block">
                    @forelse($availableYears as $year)
                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @empty
                        <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                    @endforelse
                </select>
            </div>
        </form>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- District Info --}}
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Wilayah Tugas Aktif</p>
                    <p class="text-sm font-bold text-slate-800">
                        {{ $selectedDistrictId ? $assignedDistricts->firstWhere('id', $selectedDistrictId)->name : 'Seluruh Wilayah Penugasan' }}
                    </p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Tahun Anggaran</p>
                <p class="text-sm font-bold text-slate-800">{{ $selectedYear }}</p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $totalTarget = $dashboard->sum('target_amount');
                $totalRealization = $dashboard->sum('total_realization');
                $avgPercentage = $totalTarget > 0 ? ($totalRealization / $totalTarget) * 100 : 0;
                $remainingTarget = max(0, $totalTarget - $totalRealization);
            @endphp

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Target Wilayah</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Realisasi</p>
                <p class="text-2xl font-bold text-emerald-600">Rp {{ number_format($totalRealization, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Sisa Target</p>
                <p class="text-2xl font-bold text-orange-600">Rp {{ number_format($remainingTarget, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Persentase Capaian</p>
                <p class="text-2xl font-bold text-emerald-600">{{ number_format($avgPercentage, 2, ',', '.') }}%</p>
            </div>
        </div>

        {{-- Realization Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30">
                <h3 class="font-bold text-slate-800 text-sm italic uppercase tracking-wider">Progres Realisasi Capaian</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Jenis Pajak</th>
                            <th class="px-6 py-4 text-right">Target</th>
                            <th class="px-6 py-4 text-right">Q1</th>
                            <th class="px-6 py-4 text-right">Q2</th>
                            <th class="px-6 py-4 text-right">Q3</th>
                            <th class="px-6 py-4 text-right">Q4</th>
                            <th class="px-6 py-4 text-right">Total</th>
                            <th class="px-6 py-4 text-center">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($dashboard as $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900 border-r border-slate-100">
                                    {{ $item['tax_type_name'] }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-slate-700">
                                    {{ number_format($item['target_amount'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-slate-500">
                                    {{ number_format($item['q1'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-slate-500">
                                    {{ number_format($item['q2'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-slate-500">
                                    {{ number_format($item['q3'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-slate-500 border-r border-slate-100">
                                    {{ number_format($item['q4'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-emerald-700 bg-emerald-50/30">
                                    {{ number_format($item['total_realization'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $item['achievement_percentage'] >= 100 ? 'bg-emerald-100 text-emerald-800' : ($item['achievement_percentage'] >= 50 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($item['achievement_percentage'], 2, ',', '.') }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-slate-400">
                                    <p class="text-sm italic">Tidak ada data target/realisasi untuk filter ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.employee>

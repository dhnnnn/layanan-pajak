<x-layouts.admin title="Dashboard Admin" header="Dashboard Realisasi Pajak">
    <x-slot:headerActions>
        <form action="{{ route('admin.dashboard') }}" method="GET" class="flex items-center gap-2">
            <label for="year" class="text-sm font-medium text-slate-600">Tahun:</label>
            <select name="year" id="year" onchange="this.form.submit()" class="text-sm rounded-lg bg-slate-50 text-slate-700 py-1.5 px-3 focus:bg-white focus:ring-2 focus:ring-blue-500/20 block">
                @forelse($availableYears as $year)
                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                @empty
                    <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                @endforelse
            </select>
        </form>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $totalTarget = $dashboard->sum('target_amount');
                $totalRealization = $dashboard->sum('total_realization');
                $avgPercentage = $totalTarget > 0 ? ($totalRealization / $totalTarget) * 100 : 0;
                $remainingTarget = max(0, $totalTarget - $totalRealization);
            @endphp

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Target (APBD)</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Realisasi</p>
                <p class="text-2xl font-bold text-blue-600">Rp {{ number_format($totalRealization, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Sisa Target</p>
                <p class="text-2xl font-bold text-orange-600">Rp {{ number_format($remainingTarget, 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Persentase Capaian</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-emerald-600">{{ number_format($avgPercentage, 2, ',', '.') }}%</p>
                </div>
            </div>
        </div>

        {{-- Realization Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Rincian Realisasi Per Jenis Pajak ({{ $selectedYear }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4 whitespace-nowrap">Jenis Pajak</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right">Target</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right">Q1</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right">Q2</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right">Q3</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right">Q4</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right">Total</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right">Sisa</th>
                            <th class="px-6 py-4 whitespace-nowrap text-center">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($dashboard as $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900 border-r border-slate-100">
                                    <div>{{ $item['tax_type_name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $item['tax_type_code'] }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-medium">
                                    {{ number_format($item['target_amount'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-slate-600">
                                    {{ number_format($item['q1'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-slate-600">
                                    {{ number_format($item['q2'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-slate-600">
                                    {{ number_format($item['q3'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-slate-600 border-r border-slate-100">
                                    {{ number_format($item['q4'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-blue-700 bg-blue-50/30">
                                    {{ number_format($item['total_realization'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-orange-700">
                                    {{ number_format($item['remaining_target'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $item['achievement_percentage'] >= 100 ? 'bg-emerald-100 text-emerald-800' : ($item['achievement_percentage'] >= 50 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($item['achievement_percentage'], 2, ',', '.') }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-10 text-center text-slate-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p>Belum ada data realisasi untuk tahun {{ $selectedYear }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($dashboard->isNotEmpty())
                    <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-200">
                        <tr>
                            <td class="px-6 py-4">TOTAL</td>
                            <td class="px-6 py-4 text-right">{{ number_format($totalTarget, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($dashboard->sum('q1'), 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($dashboard->sum('q2'), 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($dashboard->sum('q3'), 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($dashboard->sum('q4'), 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-blue-700 bg-blue-50/50">{{ number_format($totalRealization, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-orange-700">{{ number_format($remainingTarget, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $avgPercentage >= 100 ? 'bg-emerald-100 text-emerald-800' : ($avgPercentage >= 50 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                    {{ number_format($avgPercentage, 2, ',', '.') }}%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>

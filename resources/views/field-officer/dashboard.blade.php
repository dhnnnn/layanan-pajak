<x-layouts.field-officer title="Dashboard Petugas Lapangan" header="Dashboard Monitoring WP">
    <x-slot:headerActions>
        <form action="{{ route('pegawai.monitoring.index') }}" method="GET" class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-500 uppercase">Tahun:</span>
            <div class="relative">
                <select name="year" onchange="this.form.submit()" class="appearance-none bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 pr-8 hover:bg-slate-50 cursor-pointer">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                <svg class="w-4 h-4 absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </form>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total WP</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($summary['total_wp'], 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Ketetapan</p>
                <p class="text-2xl font-bold text-blue-600">Rp {{ number_format($summary['total_ketetapan'], 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Bayar</p>
                <p class="text-2xl font-bold text-emerald-600">Rp {{ number_format($summary['total_bayar'], 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Tunggakan</p>
                <p class="text-2xl font-bold text-orange-600">Rp {{ number_format($summary['total_tunggakan'], 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Persentase</p>
                <p class="text-2xl font-bold {{ $summary['persentase'] >= 100 ? 'text-emerald-600' : ($summary['persentase'] >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ number_format($summary['persentase'], 2, ',', '.') }}%
                </p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-800 text-sm mb-4">Menu Monitoring</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <a href="{{ route('pegawai.monitoring.tunggakan') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-orange-300 hover:bg-orange-50 transition-colors group">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center group-hover:bg-orange-200 transition-colors">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-700 text-center">Daftar Tunggakan</span>
                </a>

                <a href="{{ route('pegawai.monitoring.wp-per-kecamatan') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-blue-300 hover:bg-blue-50 transition-colors group">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-700 text-center">WP per Kecamatan</span>
                </a>

                <a href="{{ route('pegawai.monitoring.pencapaian-target') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 transition-colors group">
                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-700 text-center">Pencapaian Target</span>
                </a>

                <a href="{{ route('pegawai.monitoring.realisasi-bulanan') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-purple-300 hover:bg-purple-50 transition-colors group">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-700 text-center">Realisasi Bulanan</span>
                </a>

                <a href="{{ route('pegawai.monitoring.status-pembayaran') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-cyan-300 hover:bg-cyan-50 transition-colors group">
                    <div class="w-12 h-12 bg-cyan-100 rounded-full flex items-center justify-center group-hover:bg-cyan-200 transition-colors">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-700 text-center">Status Pembayaran</span>
                </a>

                <a href="{{ route('pegawai.monitoring.pencarian') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg border border-slate-200 hover:border-slate-400 hover:bg-slate-50 transition-colors group">
                    <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center group-hover:bg-slate-200 transition-colors">
                        <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-slate-700 text-center">Pencarian WP</span>
                </a>
            </div>
        </div>

        {{-- Districts List --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30">
                <h3 class="font-bold text-slate-800 text-sm italic uppercase tracking-wider">Kecamatan yang Diampu</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Kecamatan</th>
                            <th class="px-6 py-4 text-right">Total WP</th>
                            <th class="px-6 py-4 text-right">Ketetapan</th>
                            <th class="px-6 py-4 text-right">Bayar</th>
                            <th class="px-6 py-4 text-right">Tunggakan</th>
                            <th class="px-6 py-4 text-center">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($districts as $district)
                            @php
                                $wpStats = \Illuminate\Support\Facades\DB::table('simpadu_tax_payers')
                                    ->where('year', $year)
                                    ->where('status', '1')
                                    ->where('kd_kecamatan', $district->simpadu_code)
                                    ->selectRaw('COUNT(*) as total_wp, COALESCE(SUM(total_ketetapan), 0) as ketetapan, COALESCE(SUM(total_bayar), 0) as bayar, COALESCE(SUM(total_tunggakan), 0) as tunggakan')
                                    ->first();
                                $pct = $wpStats && $wpStats->ketetapan > 0 ? ($wpStats->bayar / $wpStats->ketetapan) * 100 : 0;
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $district->name }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">{{ number_format($wpStats->total_wp ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">Rp {{ number_format($wpStats->ketetapan ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-emerald-600 font-medium">Rp {{ number_format($wpStats->bayar ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-orange-600 font-medium">Rp {{ number_format($wpStats->tunggakan ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $pct >= 100 ? 'bg-emerald-100 text-emerald-800' : ($pct >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($pct, 1, ',', '.') }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                    <p class="text-sm italic">Tidak ada kecamatan yang diampu.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.field-officer>



<x-layouts.admin :title="'Monitoring WP ' . $employee->name" :header="'Detil Realisasi Petugas: ' . $employee->name">
    <x-slot:headerActions>
        <form method="GET" action="{{ route('admin.realization-monitoring.employee', [$upt, $employee]) }}" id="filterForm" class="flex items-center gap-2">
            {{-- Year Dropdown --}}
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">
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

        <a href="{{ route('admin.realization-monitoring.show', [$upt, 'year' => $year]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    {{-- Performance Summary Container --}}
    <div class="mb-8">
        {{-- Progress Bar --}}
        <div class="bg-slate-900 rounded-2xl p-6 shadow-2xl relative overflow-hidden mb-6">
            <div class="absolute top-0 right-0 p-8 opacity-10">
                <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/>
                </svg>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-white ring-1 ring-white/20">
                            <span class="text-xl font-black">{{ strtoupper(substr($employee->name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <h3 class="text-white text-lg font-black uppercase tracking-widest leading-none mb-1">{{ $employee->name }}</h3>
                            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                                Wilayah Tugas: <span class="text-blue-400">{{ $employee->districts->pluck('name')->implode(', ') }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="text-left md:text-right mt-4 md:mt-0">
                        <span class="text-white text-4xl font-black">{{ number_format($summary['attainment'], 1) }}%</span>
                    </div>
                </div>
                
                <div class="w-full bg-slate-800 rounded-full h-4 ring-1 ring-white/10 p-1">
                    <div class="h-full rounded-full transition-all duration-1000 ease-out {{ $summary['attainment'] >= 90 ? 'bg-emerald-400' : ($summary['attainment'] >= 50 ? 'bg-amber-400' : 'bg-rose-500') }}"
                        style="width: {{ min($summary['attainment'], 100) }}%"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Total Ketetapan</p>
                        <p class="text-xl font-black text-slate-900">Rp {{ number_format($summary['total_sptpd'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Total Pembayaran</p>
                        <p class="text-xl font-black text-emerald-600">Rp {{ number_format($summary['total_bayar'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Total Tunggakan</p>
                        <p class="text-xl font-black text-rose-600">Rp {{ number_format($summary['total_tunggakan'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- WP Details Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">Daftar Wajib Pajak Penanganan</h4>
            <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-1 rounded-full font-bold">{{ $wpData->count() }} WP Terdeteksi</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Wajib Pajak / NPWPD</th>
                        <th class="px-6 py-4 text-center">Status WP</th>
                        <th class="px-6 py-4 text-right">Jml SPTPD</th>
                        <th class="px-6 py-4 text-right">Jml Bayar</th>
                        <th class="px-6 py-4 text-right">Selisih</th>
                        <th class="px-6 py-4 text-right">Tunggakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($wpData as $wp)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900 leading-tight uppercase">{{ $wp['nm_wp'] }}</div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $wp['npwpd'] }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($wp['status_code'] == '1')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter bg-emerald-100 text-emerald-700 border border-emerald-200">AKTIF</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter bg-rose-100 text-rose-700 border border-rose-200">NON AKTIF</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-600">
                                {{ number_format($wp['total_sptpd'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-600">
                                {{ number_format($wp['total_bayar'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold {{ $wp['selisih'] >= 0 ? 'text-blue-600' : 'text-slate-400' }}">
                                {{ number_format($wp['selisih'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($wp['tunggakan'] > 0)
                                    <span class="font-black text-rose-600">
                                        {{ number_format($wp['tunggakan'], 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-slate-400 italic">Tidak ada data Wajib Pajak untuk wilayah petugas ini pada tahun {{ $year }}.</p>
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

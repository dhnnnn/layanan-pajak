<x-layouts.employee title="Data Realisasi Saya" header="Riwayat Input Realisasi Pajak">
    <x-slot:headerActions>
        <a href="{{ route('pegawai.realizations.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Input Realisasi Manual
        </a>
    </x-slot:headerActions>

    @if($districts->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
            <div class="flex flex-col items-center">
                <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                </svg>
                <p class="font-medium text-slate-700 mb-1">Belum ada kecamatan yang ditugaskan</p>
                <p class="text-sm text-slate-500">Silakan hubungi administrator untuk menugaskan kecamatan kepada Anda</p>
            </div>
        </div>
    @else
        <!-- Filter Tahun & Search -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4 items-start md:items-center">
                <div class="flex items-center gap-4">
                    <label for="yearFilter" class="text-sm font-semibold text-slate-700">Filter Tahun:</label>
                    <select id="yearFilter" class="text-sm rounded-lg bg-slate-50 text-slate-700 px-3 py-1.5 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                        @for($y = (int) date('Y'); $y <= (int) date('Y') + 2; $y++)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="flex-1"></div>
                <form method="GET" action="{{ route('pegawai.realizations.index') }}" class="flex gap-2 w-full md:w-auto">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <div class="relative flex-1 md:w-64">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Cari nama kecamatan..."
                            class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Cari
                    </button>
                    @if($search)
                        <a href="{{ route('pegawai.realizations.index', ['year' => $year]) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                            Reset
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Cards per Kecamatan -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($districts as $district)
                @php
                    $realizationCount = \App\Models\TaxRealization::query()
                        ->where('user_id', auth()->id())
                        ->where('district_id', $district->id)
                        ->where('year', $year)
                        ->count();
                    $taxTypesCount = \App\Models\TaxType::count();
                    $hasData = $realizationCount > 0;
                    $isComplete = $taxTypesCount > 0 && $realizationCount >= $taxTypesCount;
                @endphp
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900">{{ $district->name }}</h3>
                                </div>
                            </div>
                            @if($isComplete)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold shrink-0">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Lengkap
                                </span>
                            @elseif($hasData)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold shrink-0">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                    Proses
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 bg-slate-100 text-slate-500 rounded-full text-xs font-semibold shrink-0">Belum ada data</span>
                            @endif
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">Petugas Lapangan:</span>
                                <span class="font-semibold text-slate-900 text-right">{{ auth()->user()->name }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">Data Realisasi:</span>
                                <span class="font-semibold text-slate-900">{{ $realizationCount }} / {{ $taxTypesCount }} jenis pajak</span>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                                <span>Progress {{ $year }}</span>
                                <span class="font-semibold">{{ $taxTypesCount > 0 ? round(($realizationCount / $taxTypesCount) * 100) : 0 }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ $taxTypesCount > 0 ? ($realizationCount / $taxTypesCount) * 100 : 0 }}%"></div>
                            </div>
                        </div>

                        <a href="{{ route('pegawai.daily-entries.show', [$district->id, 'year' => $year]) }}"
                            class="w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Kelola Realisasi
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <script>
        document.getElementById('yearFilter').addEventListener('change', function () {
            window.location.href = '{{ route("pegawai.realizations.index") }}?year=' + this.value;
        });
    </script>
</x-layouts.employee>

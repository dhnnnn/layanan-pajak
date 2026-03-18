<x-layouts.admin :title="'Progress ' . $employee->name" :header="'Progress Realisasi: ' . $employee->name">
    <x-slot:headerActions>
        <form method="GET" action="{{ route('admin.realization-monitoring.employee', [$upt, $employee]) }}" id="filterForm" class="flex items-center gap-2">
            {{-- Month Dropdown --}}
            <div class="relative" id="monthDropdownWrapper">
                <button type="button" id="monthDropdownBtn"
                    class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <span id="monthDropdownLabel">{{ $months[$month] }}</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <input type="hidden" name="month" id="monthValue" value="{{ $month }}">
                <div id="monthDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-36 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                    @foreach($months as $num => $label)
                        <button type="button" data-value="{{ $num }}" class="month-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ $month == $num ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Year Dropdown --}}
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">
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

        <a href="{{ route('admin.realization-monitoring.export', ['upt' => $upt, 'year' => $year, 'month' => $month]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export Excel
        </a>

        <a href="{{ route('admin.realization-monitoring.show', [$upt, 'year' => $year]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">UPT</p>
            <p class="text-lg font-bold text-slate-900">{{ $upt->name }}</p>
            <p class="text-xs text-slate-400 font-mono">{{ $upt->code }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Target UPT {{ $year }}</p>
            <p class="text-xl font-bold text-blue-600">Rp {{ number_format($uptTarget, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Realisasi {{ $year }}</p>
            <p class="text-xl font-bold text-green-600">Rp {{ number_format($yearlyTotal, 0, ',', '.') }}</p>
            @if($uptTarget > 0)
                <p class="text-xs text-slate-400 mt-1">{{ number_format($progress, 1) }}% dari target</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Bulan {{ $months[$month] }}</p>
            <p class="text-xl font-bold text-slate-900">Rp {{ number_format($monthlyTotal, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-semibold text-slate-700">Progress Realisasi {{ $year }}</p>
            <span class="text-sm font-bold {{ $progress >= 100 ? 'text-green-600' : ($progress >= 50 ? 'text-blue-600' : 'text-orange-500') }}">
                {{ number_format($progress, 1) }}%
            </span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-3">
            <div class="h-3 rounded-full transition-all {{ $progress >= 100 ? 'bg-green-500' : ($progress >= 50 ? 'bg-blue-500' : 'bg-orange-400') }}"
                style="width: {{ min($progress, 100) }}%"></div>
        </div>
        @if($uptTarget > 0)
            <p class="text-xs text-slate-400 mt-2">
                Rp {{ number_format($yearlyTotal, 0, ',', '.') }} dari Rp {{ number_format($uptTarget, 0, ',', '.') }}
            </p>
        @endif
    </div>

    {{-- Wilayah Tabs + Entries --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        {{-- Wilayah Filter --}}
        @if($employee->districts->count() > 0)
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-100 flex items-center gap-2 flex-wrap">
                <span class="text-xs text-slate-500 mr-1">Wilayah:</span>
                <button type="button" onclick="filterDistrict(null)"
                    class="district-tab active-tab px-2.5 py-1 rounded-full text-xs font-semibold border transition-colors bg-blue-600 text-white border-blue-600"
                    data-district="all">
                    Semua
                </button>
                @foreach($employee->districts as $district)
                    <button type="button" onclick="filterDistrict('{{ $district->id }}')"
                        class="district-tab px-2.5 py-1 rounded-full text-xs font-semibold border transition-colors bg-white text-slate-600 border-slate-200 hover:border-blue-400 hover:text-blue-600"
                        data-district="{{ $district->id }}">
                        {{ $district->name }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Entries Table --}}
        @if($monthlyEntries->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap" id="entriesTable">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-xs">
                        <tr>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Jenis Pajak</th>
                            <th class="px-5 py-3">Kecamatan</th>
                            <th class="px-5 py-3 text-right">Jumlah</th>
                            <th class="px-5 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($monthlyEntries as $entry)
                            <tr class="hover:bg-slate-50 entry-row" data-district="{{ $entry->district_id }}">
                                <td class="px-5 py-3 font-mono">{{ $entry->entry_date->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">{{ $entry->taxType->name }}</td>
                                <td class="px-5 py-3">{{ $entry->district->name }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-green-600">
                                    Rp {{ number_format($entry->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-slate-400">{{ $entry->note ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="entriesEmpty" class="hidden px-6 py-8 text-center text-sm text-slate-400 italic">
                Tidak ada data di wilayah ini.
            </div>
        @else
            <div class="px-6 py-10 text-center text-sm text-slate-400 italic">
                Belum ada input di {{ $months[$month] }} {{ $year }}.
            </div>
        @endif
    </div>

    <script>
        function filterDistrict(districtId) {
            document.querySelectorAll('.district-tab').forEach(btn => {
                const isActive = districtId === null
                    ? btn.dataset.district === 'all'
                    : btn.dataset.district === districtId;

                if (isActive) {
                    btn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
                    btn.classList.remove('bg-white', 'text-slate-600', 'border-slate-200', 'hover:border-blue-400', 'hover:text-blue-600');
                } else {
                    btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                    btn.classList.add('bg-white', 'text-slate-600', 'border-slate-200', 'hover:border-blue-400', 'hover:text-blue-600');
                }
            });

            const table = document.getElementById('entriesTable');
            if (!table) { return; }

            let visibleCount = 0;
            table.querySelectorAll('.entry-row').forEach(row => {
                const show = districtId === null || row.dataset.district === districtId;
                row.style.display = show ? '' : 'none';
                if (show) { visibleCount++; }
            });

            document.getElementById('entriesEmpty').style.display = visibleCount === 0 ? '' : 'none';
        }

        // Month dropdown
        document.getElementById('monthDropdownBtn').addEventListener('click', () => {
            document.getElementById('monthDropdownMenu').classList.toggle('hidden');
        });

        // Year dropdown
        document.getElementById('yearDropdownBtn').addEventListener('click', () => {
            document.getElementById('yearDropdownMenu').classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!document.getElementById('monthDropdownWrapper').contains(e.target)) {
                document.getElementById('monthDropdownMenu').classList.add('hidden');
            }
            if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                document.getElementById('yearDropdownMenu').classList.add('hidden');
            }
        });

        document.querySelectorAll('.month-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                document.getElementById('monthValue').value = this.dataset.value;
                document.getElementById('monthDropdownLabel').textContent = this.textContent.trim();
                document.getElementById('monthDropdownMenu').classList.add('hidden');
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

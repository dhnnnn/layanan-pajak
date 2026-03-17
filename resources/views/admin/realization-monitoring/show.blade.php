<x-layouts.admin :title="'Monitoring ' . $upt->name" :header="'Monitoring Realisasi: ' . $upt->name">
    <x-slot:headerActions>
        <form method="GET" action="{{ route('admin.realization-monitoring.show', $upt) }}" id="filterForm" class="flex items-center gap-2">
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

        {{-- Export Button --}}
        <a href="{{ route('admin.realization-monitoring.export', ['upt' => $upt, 'year' => $year, 'month' => $month]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export Excel
        </a>

        <a href="{{ route('admin.realization-monitoring.index', ['year' => $year]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    {{-- UPT Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Total Pegawai</p>
            <p class="text-2xl font-bold text-slate-900">{{ count($employeeData) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Target APBD {{ $year }}</p>
            <p class="text-2xl font-bold text-blue-600">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Total Realisasi {{ $year }}</p>
            <p class="text-2xl font-bold text-green-600">Rp {{ number_format($uptYearlyTotal, 0, ',', '.') }}</p>
            @if($totalTarget > 0)
                <p class="text-xs text-slate-400 mt-1">{{ number_format(($uptYearlyTotal / $totalTarget) * 100, 1) }}% dari target</p>
            @endif
        </div>
    </div>

    {{-- Per-Employee Cards --}}
    @forelse($employeeData as $data)
        @php $employee = $data['employee']; @endphp
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-4">
            {{-- Employee Header --}}
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <span class="text-sm font-bold text-blue-600">{{ strtoupper(substr($employee->name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">{{ $employee->name }}</p>
                        <p class="text-xs text-slate-400">{{ $employee->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-6 text-right">
                    <div>
                        <p class="text-xs text-slate-500">Realisasi {{ $year }}</p>
                        <p class="font-bold text-green-600 text-sm">Rp {{ number_format($data['yearly_total'], 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Bulan ini</p>
                        <p class="font-bold text-blue-600 text-sm">Rp {{ number_format($data['monthly_total'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-28">
                        <p class="text-xs text-slate-500 mb-1">Progress</p>
                        <div class="flex items-center gap-1.5">
                            <div class="flex-1 bg-slate-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full {{ $data['progress'] >= 100 ? 'bg-green-500' : ($data['progress'] >= 50 ? 'bg-blue-500' : 'bg-orange-400') }}"
                                    style="width: {{ min($data['progress'], 100) }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-slate-600">{{ number_format($data['progress'], 1) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Wilayah Filter Tabs --}}
            @if($employee->districts->count() > 0)
                <div class="px-6 py-3 bg-slate-50 border-b border-slate-100 flex items-center gap-2 flex-wrap"
                    id="district-tabs-{{ $loop->index }}">
                    <span class="text-xs text-slate-500 mr-1">Wilayah:</span>
                    <button type="button"
                        onclick="filterDistrict({{ $loop->index }}, null)"
                        class="district-tab district-tab-{{ $loop->index }} active-tab px-2.5 py-1 rounded-full text-xs font-semibold border transition-colors bg-blue-600 text-white border-blue-600"
                        data-district="all">
                        Semua
                    </button>
                    @foreach($employee->districts as $district)
                        <button type="button"
                            onclick="filterDistrict({{ $loop->parent->index }}, '{{ $district->id }}')"
                            class="district-tab district-tab-{{ $loop->parent->index }} px-2.5 py-1 rounded-full text-xs font-semibold border transition-colors bg-white text-slate-600 border-slate-200 hover:border-blue-400 hover:text-blue-600"
                            data-district="{{ $district->id }}">
                            {{ $district->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Monthly Entries --}}
            @if($data['monthly_entries']->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left text-slate-600" id="entries-table-{{ $loop->index }}">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                            <tr>
                                <th class="px-6 py-2">Tanggal</th>
                                <th class="px-6 py-2">Jenis Pajak</th>
                                <th class="px-6 py-2">Kecamatan</th>
                                <th class="px-6 py-2 text-right">Jumlah</th>
                                <th class="px-6 py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($data['monthly_entries'] as $entry)
                                <tr class="hover:bg-slate-50 entry-row" data-district="{{ $entry->district_id }}">
                                    <td class="px-6 py-2 font-mono">{{ $entry->entry_date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-2">{{ $entry->taxType->name }}</td>
                                    <td class="px-6 py-2">{{ $entry->district->name }}</td>
                                    <td class="px-6 py-2 text-right font-semibold text-green-600">
                                        Rp {{ number_format($entry->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-2 text-slate-400">{{ $entry->note ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="entries-empty-{{ $loop->index }}" class="hidden px-6 py-4 text-center text-xs text-slate-400 italic">
                    Data di wilayah ini masih belum tersedia.
                </div>
            @else
                <div class="px-6 py-4 text-center text-xs text-slate-400 italic">
                    Belum ada input di {{ $months[$month] }} {{ $year }}.
                </div>
            @endif
        </div>
    @empty
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center text-slate-500">
            Belum ada pegawai di UPT ini.
        </div>
    @endforelse

    <script>
        function filterDistrict(cardIndex, districtId) {
            // Update tab styles
            document.querySelectorAll(`.district-tab-${cardIndex}`).forEach(btn => {
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

            // Filter rows
            const table = document.getElementById(`entries-table-${cardIndex}`);
            if (!table) return;

            let visibleCount = 0;
            table.querySelectorAll('.entry-row').forEach(row => {
                const show = districtId === null || row.dataset.district === districtId;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            // Show/hide empty state
            const emptyEl = document.getElementById(`entries-empty-${cardIndex}`);
            if (emptyEl) emptyEl.style.display = visibleCount === 0 ? '' : 'none';
        }

        // Month dropdown
        const monthBtn = document.getElementById('monthDropdownBtn');
        const monthMenu = document.getElementById('monthDropdownMenu');
        const monthValue = document.getElementById('monthValue');
        const monthLabel = document.getElementById('monthDropdownLabel');

        monthBtn.addEventListener('click', () => monthMenu.classList.toggle('hidden'));

        document.addEventListener('click', function (e) {
            if (!document.getElementById('monthDropdownWrapper').contains(e.target)) {
                monthMenu.classList.add('hidden');
            }
            if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                document.getElementById('yearDropdownMenu').classList.add('hidden');
            }
        });

        document.querySelectorAll('.month-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                monthValue.value = this.dataset.value;
                monthLabel.textContent = this.textContent.trim();
                monthMenu.classList.add('hidden');
                document.getElementById('filterForm').submit();
            });
        });

        // Year dropdown
        const yearBtn = document.getElementById('yearDropdownBtn');
        const yearMenu = document.getElementById('yearDropdownMenu');
        const yearValue = document.getElementById('yearValue');
        const yearLabel = document.getElementById('yearDropdownLabel');

        yearBtn.addEventListener('click', () => yearMenu.classList.toggle('hidden'));

        document.querySelectorAll('.year-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                yearValue.value = this.dataset.value;
                yearLabel.textContent = this.textContent.trim();
                yearMenu.classList.add('hidden');
                document.getElementById('filterForm').submit();
            });
        });
    </script>
</x-layouts.admin>

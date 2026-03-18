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

    {{-- Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Total Pegawai</p>
            <p class="text-2xl font-bold text-slate-900">{{ count($employeeData) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Target {{ $year }}</p>
            <p class="text-2xl font-bold text-blue-600">Rp {{ number_format($uptTarget, 0, ',', '.') }}</p>
            @if($uptTarget == 0)
                <p class="text-xs text-slate-400 mt-1 italic">Belum ada target per UPT</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Total Realisasi {{ $year }}</p>
            <p class="text-2xl font-bold text-green-600">Rp {{ number_format($uptYearlyTotal, 0, ',', '.') }}</p>
            @if($uptTarget > 0)
                <p class="text-xs text-slate-400 mt-1">{{ number_format(($uptYearlyTotal / $uptTarget) * 100, 1) }}% dari target</p>
            @endif
        </div>
    </div>

    {{-- Employee Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-5 py-3">Pegawai</th>
                        <th class="px-5 py-3 text-right">Realisasi {{ $year }}</th>
                        <th class="px-5 py-3">Progress</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($employeeData as $data)
                        @php $barWidth = min($data['progress'], 100); @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                        <span class="text-xs font-bold text-blue-600">{{ strtoupper(substr($data['employee']->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $data['employee']->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $data['employee']->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right font-semibold text-green-600">
                                Rp {{ number_format($data['yearly_total'], 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 min-w-[200px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-slate-100 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $data['progress'] >= 100 ? 'bg-green-500' : ($data['progress'] >= 50 ? 'bg-blue-500' : 'bg-orange-400') }}"
                                            style="width: {{ $barWidth }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-600 w-14 text-right">{{ number_format($data['progress'], 1) }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.realization-monitoring.employee', [$upt, $data['employee'], 'year' => $year]) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                    Lihat Progress
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                Belum ada pegawai di UPT ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('monthDropdownBtn').addEventListener('click', () => {
            document.getElementById('monthDropdownMenu').classList.toggle('hidden');
        });

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

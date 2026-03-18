<x-layouts.admin title="Monitoring Realisasi" header="Monitoring Realisasi per UPT">
    <x-slot:headerActions>
        <form method="GET" action="{{ route('admin.realization-monitoring.index') }}" id="yearForm">
            <div class="relative" id="yearDropdownWrapper">
                <button type="button" id="yearDropdownBtn"
                    class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 font-medium hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span id="yearDropdownLabel">{{ $year }}</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <input type="hidden" name="year" id="yearValue" value="{{ $year }}">
                <div id="yearDropdownMenu" class="hidden absolute right-0 z-20 mt-1 w-36 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                    @foreach($availableYears as $y)
                        <button type="button" data-value="{{ $y }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ $year == $y ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                            {{ $y }}
                        </button>
                    @endforeach
                </div>
            </div>
        </form>
    </x-slot:headerActions>

    {{-- Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Total UPT</p>
            <p class="text-2xl font-bold text-slate-900">{{ $upts->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Target APBD {{ $year }}</p>
            <p class="text-2xl font-bold text-blue-600">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Total Realisasi {{ $year }}</p>
            <p class="text-2xl font-bold text-green-600">Rp {{ number_format(array_sum($uptTotals), 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- UPT Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">UPT</th>
                        <th class="px-4 py-3 text-center">Pegawai</th>
                        <th class="px-4 py-3 text-right">Realisasi {{ $year }}</th>
                        <th class="px-4 py-3">Progress</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($upts as $upt)
                        @php
                            $total = $uptTotals[$upt->id] ?? 0;
                            $uptTarget = $uptTargets[$upt->id] ?? 0;
                            $rawProgress = $uptTarget > 0 ? ($total / $uptTarget) * 100 : 0;
                            $barWidth = min($rawProgress, 100);
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $upt->name }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $upt->code }}</div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                    {{ $upt->users_count }} pegawai
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right font-semibold text-green-600">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 min-w-[180px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-slate-100 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $rawProgress >= 100 ? 'bg-green-500' : ($rawProgress >= 50 ? 'bg-blue-500' : 'bg-orange-400') }}"
                                            style="width: {{ $barWidth }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-600 w-14 text-right">{{ number_format($rawProgress, 1) }}%</span>
                                </div>
                                @if($uptTarget > 0)
                                    <div class="text-xs text-slate-400 mt-1">Target: Rp {{ number_format($uptTarget, 0, ',', '.') }}</div>
                                @else
                                    <div class="text-xs text-slate-400 mt-1 italic">Belum ada target</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('admin.realization-monitoring.show', [$upt, 'year' => $year]) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                Belum ada UPT terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const btn = document.getElementById('yearDropdownBtn');
        const menu = document.getElementById('yearDropdownMenu');
        const yearValue = document.getElementById('yearValue');
        const yearLabel = document.getElementById('yearDropdownLabel');

        btn.addEventListener('click', () => menu.classList.toggle('hidden'));

        document.addEventListener('click', function (e) {
            if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        document.querySelectorAll('.year-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                yearValue.value = this.dataset.value;
                yearLabel.textContent = this.textContent.trim();
                menu.classList.add('hidden');
                document.getElementById('yearForm').submit();
            });
        });
    </script>
</x-layouts.admin>

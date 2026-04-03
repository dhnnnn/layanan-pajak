<x-layouts.field-officer title="Pencapaian Target" header="Progress Pencapaian Target Pajak">
    <x-slot:headerActions>
        <form action="{{ route('pegawai.monitoring.pencapaian-target') }}" method="GET" class="flex items-center gap-2">
            <select name="year" onchange="this.form.submit()" class="appearance-none bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 pr-8 hover:bg-slate-50 cursor-pointer">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Target</p>
                <p class="text-2xl font-bold text-blue-600">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Realisasi</p>
                <p class="text-2xl font-bold text-emerald-600">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Persentase Capaian</p>
                <p class="text-2xl font-bold {{ $totalPersentase >= 100 ? 'text-emerald-600' : ($totalPersentase >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ number_format($totalPersentase, 2, ',', '.') }}%
                </p>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-slate-700">Progress Capaian</span>
                <span class="text-sm font-bold text-slate-900">{{ number_format($totalPersentase, 1, ',', '.') }}%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-4">
                <div class="h-full rounded-full transition-all duration-700 {{ $totalPersentase >= 100 ? 'bg-emerald-500' : ($totalPersentase >= 50 ? 'bg-amber-400' : 'bg-red-500') }}"
                    style="width: {{ min($totalPersentase, 100) }}%"></div>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Jenis Pajak</th>
                            <th class="px-6 py-4 text-right">Target</th>
                            <th class="px-6 py-4 text-right">Realisasi</th>
                            <th class="px-6 py-4">Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($targetData as $data)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $data['jenis_pajak'] }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">Rp {{ number_format($data['target'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-emerald-600 font-medium">Rp {{ number_format($data['realisasi'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 min-w-[150px]">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $data['persentase'] >= 100 ? 'bg-emerald-100 text-emerald-800' : ($data['persentase'] >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($data['persentase'], 1, ',', '.') }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-400">
                                    <p class="text-sm italic">Tidak ada data target pajak.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.field-officer>



<x-layouts.field-officer title="Realisasi Bulanan" header="Data Realisasi Pembayaran per Bulan">
    <x-slot:headerActions>
        <form action="{{ route('field-officer.monitoring.monthly-realization') }}" method="GET" class="flex items-center gap-2">
            <select name="year" onchange="this.form.submit()" class="appearance-none bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 pr-8 hover:bg-slate-50 cursor-pointer">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- Chart --}}
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="font-bold text-slate-800 text-sm mb-4">Grafik Realisasi per Bulan</h3>
            <div class="h-64 flex items-end justify-between gap-2">
                @foreach($monthlyData as $data)
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-blue-100 rounded-t-lg hover:bg-blue-200 transition-colors relative group" 
                             style="height: {{ $data['total_sptpd'] > 0 ? max(20, ($data['total_sptpd'] / $monthlyData->max('total_sptpd')) * 200) : 20 }}px">
                            <div class="absolute bottom-full mb-2 hidden group-hover:block bg-slate-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap z-10">
                                Rp {{ number_format($data['total_sptpd'], 0, ',', '.') }}
                            </div>
                        </div>
                        <span class="text-xs text-slate-500 mt-2">{{ $data['bulan'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-center">No</th>
                            <th class="px-6 py-4">Bulan</th>
                            <th class="px-6 py-4 text-right">Total SPTPD</th>
                            <th class="px-6 py-4">Grafik</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($monthlyData as $index => $data)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-center text-slate-500">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $data['bulan'] }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">Rp {{ number_format($data['total_sptpd'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 w-48">
                                    <div class="w-full bg-slate-100 rounded-full h-2">
                                        <div class="h-full rounded-full bg-blue-500" 
                                             style="width: {{ $data['total_sptpd'] > 0 ? ($data['total_sptpd'] / $monthlyData->max('total_sptpd')) * 100 : 0 }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-400">
                                    <p class="text-sm italic">Tidak ada data.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.field-officer>



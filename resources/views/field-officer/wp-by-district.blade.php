<x-layouts.field-officer title="WP per Kecamatan" header="Data Wajib Pajak per Kecamatan yang Diampu">
    <x-slot:headerActions>
        <form action="{{ route('field-officer.monitoring.assigned-districts') }}" method="GET" class="flex items-center gap-2">
            <select name="year" onchange="this.form.submit()" class="appearance-none bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 pr-8 hover:bg-slate-50 cursor-pointer">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </x-slot:headerActions>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Kecamatan</th>
                        <th class="px-6 py-4 text-center">Total WP</th>
                        <th class="px-6 py-4 text-right">Ketetapan</th>
                        <th class="px-6 py-4 text-right">Bayar</th>
                        <th class="px-6 py-4 text-right">Tunggakan</th>
                        <th class="px-6 py-4">Persentase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($districts as $district)
                        @php
                            $width = min($district['persentase'], 100);
                            $colorClass = $district['persentase'] >= 100 ? 'bg-emerald-500' : ($district['persentase'] >= 50 ? 'bg-amber-400' : 'bg-red-500');
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $district['name'] }}</td>
                            <td class="px-6 py-4 text-center text-slate-600">{{ number_format($district['total_wp'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">Rp {{ number_format($district['total_ketetapan'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-emerald-600 font-medium">Rp {{ number_format($district['total_bayar'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-orange-600 font-medium">Rp {{ number_format($district['total_tunggakan'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 min-w-[180px]">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 bg-slate-100 rounded-full h-2 ring-1 ring-slate-100">
                                        <div class="h-full rounded-full transition-all duration-700 {{ $colorClass }}" style="width: {{ $width }}%"></div>
                                    </div>
                                    <span class="text-xs font-black text-slate-600 w-12 text-right">{{ number_format($district['persentase'], 1) }}%</span>
                                </div>
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
</x-layouts.field-officer>



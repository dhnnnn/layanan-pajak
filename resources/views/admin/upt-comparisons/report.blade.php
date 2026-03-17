<x-layouts.admin title="Laporan Perbandingan Target UPT" header="Laporan Perbandingan Target UPT">
    <x-slot:headerActions>
        <form action="{{ route('admin.upt-comparisons.report') }}" method="GET" class="flex items-center gap-2">
            <select name="year" class="text-sm border-slate-200 rounded-lg focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                @php $currentYear = date('Y'); @endphp
                @for($y = $currentYear; $y <= $currentYear + 2; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
        <a href="{{ route('admin.upt-comparisons.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    @if($upts->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
            <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-slate-600 mb-2">Belum ada data UPT</p>
            <a href="{{ route('admin.upts.create') }}" class="text-blue-600 hover:underline font-medium">Tambah UPT</a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4 sticky left-0 bg-slate-50 z-10">No.</th>
                            <th class="px-6 py-4 sticky left-12 bg-slate-50 z-10">Jenis Pajak</th>
                            <th class="px-6 py-4 text-right">Target {{ $year }}</th>
                            @foreach($upts as $upt)
                                <th class="px-6 py-4 text-right">{{ $upt->name }}</th>
                            @endforeach
                            <th class="px-6 py-4 text-right bg-blue-50">Total {{ $upts->count() }} UPT</th>
                            <th class="px-6 py-4 text-center bg-green-50">% Target</th>
                            <th class="px-6 py-4 text-center bg-orange-50">% Selisih</th>
                            <th class="px-6 py-4 text-right bg-red-50">Selisih (Rp.)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @php
                            $taxTypes = \App\Models\TaxType::query()->orderBy('code')->get();
                            $no = 1;
                        @endphp
                        @foreach($taxTypes as $taxType)
                            @php
                                $target = \App\Models\TaxTarget::query()
                                    ->where('tax_type_id', $taxType->id)
                                    ->where('year', $year)
                                    ->first();
                                $targetAmount = $target?->target_amount ?? 0;
                                
                                $totalUpt = 0;
                                $uptTargets = [];
                                
                                foreach($upts as $upt) {
                                    $comparison = $upt->comparisons->where('tax_type_id', $taxType->id)->where('year', $year)->first();
                                    $amount = $comparison?->target_amount ?? 0;
                                    $uptTargets[$upt->id] = $amount;
                                    $totalUpt += $amount;
                                }
                                
                                $percentTarget = $targetAmount > 0 ? ($totalUpt / $targetAmount) * 100 : 0;
                                $selisih = $targetAmount - $totalUpt;
                                $percentSelisih = $targetAmount > 0 ? ($selisih / $targetAmount) * 100 : 0;
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 sticky left-0 bg-white font-medium">{{ $no++ }}</td>
                                <td class="px-6 py-4 sticky left-12 bg-white font-medium text-slate-900">{{ $taxType->name }}</td>
                                <td class="px-6 py-4 text-right font-mono text-xs">Rp {{ number_format($targetAmount, 0, ',', '.') }}</td>
                                @foreach($upts as $upt)
                                    <td class="px-6 py-4 text-right font-mono text-xs">Rp {{ number_format($uptTargets[$upt->id], 0, ',', '.') }}</td>
                                @endforeach
                                <td class="px-6 py-4 text-right font-mono text-xs font-bold bg-blue-50">Rp {{ number_format($totalUpt, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center font-bold bg-green-50 {{ $percentTarget >= 100 ? 'text-green-700' : 'text-orange-700' }}">
                                    {{ number_format($percentTarget, 1) }}%
                                </td>
                                <td class="px-6 py-4 text-center font-bold {{ abs($percentSelisih) < 0.1 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                    {{ number_format($percentSelisih, 1) }}%
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-xs font-bold {{ abs($selisih) < 1000 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                    Rp {{ number_format($selisih, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-xs text-blue-700 leading-relaxed">
                    <p class="font-bold mb-1">Keterangan:</p>
                    <ul class="list-disc ml-4 space-y-1">
                        <li><strong>% Target:</strong> Persentase total target UPT terhadap target APBD</li>
                        <li><strong>% Selisih:</strong> Persentase selisih antara target APBD dengan total target UPT</li>
                        <li><strong>Selisih:</strong> Nilai rupiah selisih (positif = kurang, negatif = lebih)</li>
                        <li>Warna hijau menunjukkan target sudah sesuai, warna merah menunjukkan ada selisih</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
</x-layouts.admin>

<x-layouts.admin title="Pratinjau Import Realisasi" :header="'Pratinjau Data: ' . $fileName">
    <x-slot:headerActions>
        <a href="{{ route('admin.import.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Batal & Kembali
        </a>
    </x-slot:headerActions>

    <div class="space-y-6"> 
        {{-- Preview Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm italic uppercase tracking-wider">Sampel Data Import</h3>
                <span class="text-[10px] text-slate-400 font-mono">{{ $fileName }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-[11px] text-left text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase border-b border-slate-200">
                        <tr>
                            <th rowspan="2" class="px-4 py-3 border-r border-slate-200">Uraian</th>
                            <th rowspan="2" class="px-4 py-3 border-r border-slate-200 text-center">Target<br>APBD {{ $year }}</th>
                            <th colspan="2" class="px-4 py-2 border-r border-slate-200 text-center bg-blue-50">Triwulan I</th>
                            <th colspan="2" class="px-4 py-2 border-r border-slate-200 text-center bg-green-50">Triwulan II</th>
                            <th colspan="2" class="px-4 py-2 border-r border-slate-200 text-center bg-yellow-50">Triwulan III</th>
                            <th colspan="2" class="px-4 py-2 border-r border-slate-200 text-center bg-purple-50">Triwulan IV</th>
                        </tr>
                        <tr class="border-t border-slate-200">
                            <th class="px-2 py-2 text-center text-[10px] bg-blue-50">Target</th>
                            <th class="px-2 py-2 text-center text-[10px] border-r border-slate-200 bg-blue-50">%</th>
                            <th class="px-2 py-2 text-center text-[10px] bg-green-50">Target</th>
                            <th class="px-2 py-2 text-center text-[10px] border-r border-slate-200 bg-green-50">%</th>
                            <th class="px-2 py-2 text-center text-[10px] bg-yellow-50">Target</th>
                            <th class="px-2 py-2 text-center text-[10px] border-r border-slate-200 bg-yellow-50">%</th>
                            <th class="px-2 py-2 text-center text-[10px] bg-purple-50">Target</th>
                            <th class="px-2 py-2 text-center text-[10px] border-r border-slate-200 bg-purple-50">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($previewData as $row)
                            @php
                                // Get target APBD
                                $target_apbd = (float) ($row['target'] ?? 0);
                                
                                // Get target per quarter from Excel
                                $q1_target = (float) ($row['q1_target'] ?? 0);
                                $q2_target = (float) ($row['q2_target'] ?? 0);
                                $q3_target = (float) ($row['q3_target'] ?? 0);
                                $q4_target = (float) ($row['q4_target'] ?? 0);
                                
                                // Calculate % Target = (Target Triwulan / Target APBD) × 100
                                $q1_target_pct = $target_apbd > 0 ? ($q1_target / $target_apbd * 100) : 0;
                                $q2_target_pct = $target_apbd > 0 ? ($q2_target / $target_apbd * 100) : 0;
                                $q3_target_pct = $target_apbd > 0 ? ($q3_target / $target_apbd * 100) : 0;
                                $q4_target_pct = $target_apbd > 0 ? ($q4_target / $target_apbd * 100) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors {{ !$row['is_valid'] ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-3 border-r border-slate-100">
                                    <div class="font-semibold text-slate-900">{{ $row['jenis_pajak'] ?? $row['uraian'] ?? '-' }}</div>
                                    @if(!empty($row['errors']))
                                        <div class="text-[10px] text-red-600 mt-1">
                                            @foreach($row['errors'] as $error)
                                                <div>• {{ $error }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="text-[9px] text-blue-600 mt-1">{{ $row['keterangan'] ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-bold border-r border-slate-100">{{ number_format($target_apbd, 0, ',', '.') }}</td>
                                
                                {{-- Triwulan I --}}
                                <td class="px-2 py-3 text-right text-[10px] border-l-2 border-blue-300">{{ number_format($q1_target, 0, ',', '.') }}</td>
                                <td class="px-2 py-3 text-right text-[10px] border-r-2 border-blue-300">{{ number_format($q1_target_pct, 2) }}%</td>
                                
                                {{-- Triwulan II --}}
                                <td class="px-2 py-3 text-right text-[10px] border-l-2 border-green-300">{{ number_format($q2_target, 0, ',', '.') }}</td>
                                <td class="px-2 py-3 text-right text-[10px] border-r-2 border-green-300">{{ number_format($q2_target_pct, 2) }}%</td>
                                
                                {{-- Triwulan III --}}
                                <td class="px-2 py-3 text-right text-[10px] border-l-2 border-yellow-300">{{ number_format($q3_target, 0, ',', '.') }}</td>
                                <td class="px-2 py-3 text-right text-[10px] border-r-2 border-yellow-300">{{ number_format($q3_target_pct, 2) }}%</td>
                                
                                {{-- Triwulan IV --}}
                                <td class="px-2 py-3 text-right text-[10px] border-l-2 border-purple-300">{{ number_format($q4_target, 0, ',', '.') }}</td>
                                <td class="px-2 py-3 text-right text-[10px] border-r-2 border-purple-300">{{ number_format($q4_target_pct, 2) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <div class="text-[11px] text-slate-500 italic">
                    * Import akan membuat/update data untuk semua {{ $districtCount }} kecamatan secara otomatis.
                </div>
                
                <form action="{{ route('admin.import.confirm') }}" method="POST">
                    @csrf
                    <input type="hidden" name="stored_path" value="{{ $storedPath }}">
                    <input type="hidden" name="file_name" value="{{ $fileName }}">
                    <input type="hidden" name="year" value="{{ $year }}">
                    
                    <button type="submit" class="px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-all shadow-md transform active:scale-95 inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Konfirmasi & Simpan Semua Data
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>

<x-layouts.admin title="Pratinjau Import Target APBD" :header="'Pratinjau Data: ' . $fileName">
    <x-slot:headerActions>
        <a href="{{ route('admin.tax-targets.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Batal & Kembali
        </a>
    </x-slot:headerActions>

    <div class="space-y-6"> 
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wider">Sampel Data Import Target APBD</h3>
                <span class="text-[10px] text-slate-400 font-mono">{{ $fileName }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-[11px] text-left text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 border-r border-slate-200">Jenis Pajak</th>
                            <th class="px-4 py-3 border-r border-slate-200 text-right">Target Total</th>
                            <th class="px-4 py-3 border-r border-slate-200 text-right">Triwulan I</th>
                            <th class="px-4 py-3 border-r border-slate-200 text-right">Triwulan II</th>
                            <th class="px-4 py-3 border-r border-slate-200 text-right">Triwulan III</th>
                            <th class="px-4 py-3 border-r border-slate-200 text-right">Triwulan IV</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($previewData as $row)
                            <tr class="hover:bg-slate-50/50 transition-colors {{ !$row['is_valid'] ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-3 border-r border-slate-100">
                                    <div class="font-semibold text-slate-900">{{ $row['uraian'] }}</div>
                                    @if(!$row['is_valid'])
                                        <div class="text-[10px] text-red-600 mt-1">
                                            @foreach($row['errors'] as $error)
                                                <div>• {{ $error }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-bold border-r border-slate-100">Rp {{ number_format($row['target_amount'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right border-r border-slate-100">Rp {{ number_format($row['q1_target'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right border-r border-slate-100">Rp {{ number_format($row['q2_target'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right border-r border-slate-100">Rp {{ number_format($row['q3_target'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right border-r border-slate-100">Rp {{ number_format($row['q4_target'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($row['is_valid'])
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-[9px] font-bold">SIAP</span>
                                    @else
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-[9px] font-bold">ERROR</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <div class="text-[11px] text-slate-500 italic">
                    * Import akan memperbarui target APBD untuk tahun {{ $year }}.
                </div>
                
                <form action="{{ route('admin.tax-targets.import') }}" method="POST">
                    @csrf
                    <input type="hidden" name="stored_path" value="{{ $storedPath }}">
                    <input type="hidden" name="file_name" value="{{ $fileName }}">
                    <input type="hidden" name="year" value="{{ $year }}">
                    
                    <button type="submit" class="px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-all shadow-md inline-flex items-center gap-2" {{ collect($previewData)->contains('is_valid', false) ? 'disabled' : '' }}>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Konfirmasi & Simpan Target
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>

<x-layouts.admin title="Pratinjau Import Perbandingan UPT" :header="'Pratinjau Data: ' . $fileName">
    <x-slot:headerActions>
        <a href="{{ route('admin.upt-comparisons.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
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
                <h3 class="font-bold text-slate-800 text-sm italic uppercase tracking-wider">Sampel Data Import Perbandingan UPT</h3>
                <span class="text-[10px] text-slate-400 font-mono">{{ $fileName }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-[11px] text-left text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase border-b border-slate-200">
                        <tr>
                            <th rowspan="2" class="px-4 py-3 border-r border-slate-200">Jenis Pajak</th>
                            <th rowspan="2" class="px-4 py-3 border-r border-slate-200 text-center">Target<br>{{ $year }}</th>
                            @foreach($upts as $upt)
                                <th rowspan="2" class="px-3 py-3 border-r border-slate-200 text-center text-[10px]">{{ $upt->name }}</th>
                            @endforeach
                            <th colspan="2" class="px-4 py-2 text-center bg-blue-50">Total</th>
                            <th colspan="2" class="px-4 py-2 text-center bg-red-50">Selisih</th>
                        </tr>
                        <tr class="border-t border-slate-200">
                            <th class="px-2 py-2 text-center text-[10px] bg-blue-50">Jumlah</th>
                            <th class="px-2 py-2 text-center text-[10px] border-r border-slate-200 bg-blue-50">%</th>
                            <th class="px-2 py-2 text-center text-[10px] bg-red-50">Jumlah</th>
                            <th class="px-2 py-2 text-center text-[10px] border-r border-slate-200 bg-red-50">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($previewData as $row)
                            <tr class="hover:bg-slate-50/50 transition-colors {{ !$row['is_valid'] ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-3 border-r border-slate-100">
                                    <div class="font-semibold text-slate-900">{{ $row['jenis_pajak'] ?? '-' }}</div>
                                    @if(!empty($row['errors']))
                                        <div class="text-[10px] text-red-600 mt-1">
                                            @foreach($row['errors'] as $error)
                                                <div>• {{ $error }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-bold border-r border-slate-100">{{ number_format($row['target'] ?? 0, 0, ',', '.') }}</td>
                                
                                {{-- UPT Values --}}
                                @foreach($upts as $upt)
                                    <td class="px-3 py-3 text-right text-[10px] border-r border-slate-100">
                                        {{ number_format($row['upt_values'][$upt->id] ?? 0, 0, ',', '.') }}
                                    </td>
                                @endforeach
                                
                                {{-- Total UPT --}}
                                <td class="px-2 py-3 text-right text-[10px] font-bold border-l-2 border-blue-300">{{ number_format($row['total_upt'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-2 py-3 text-right text-[10px] border-r-2 border-blue-300">{{ number_format($row['percent_target'] ?? 0, 2) }}%</td>
                                
                                {{-- Selisih --}}
                                <td class="px-2 py-3 text-right text-[10px] font-bold border-l-2 border-red-300">{{ number_format($row['selisih'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-2 py-3 text-right text-[10px] border-r-2 border-red-300">{{ number_format($row['percent_selisih'] ?? 0, 2) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <div class="text-[11px] text-slate-500 italic">
                    * Import akan menyimpan data perbandingan target UPT untuk tahun {{ $year }}.
                </div>
                
                <form action="{{ route('admin.upt-comparisons.import') }}" method="POST">
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

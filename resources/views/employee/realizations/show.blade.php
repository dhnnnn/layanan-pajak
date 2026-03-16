<x-layouts.employee title="Detail Realisasi" :header="'Detail: ' . $realization->taxType->name . ' (' . $realization->year . ')'">
    <x-slot:headerActions>
        <div class="flex items-center gap-2">
            <a href="{{ route('pegawai.realizations.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
            <a href="{{ route('pegawai.realizations.edit', $realization) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Edit Data
            </a>
        </div>
    </x-slot:headerActions>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm uppercase tracking-widest text-center">Rincian Realisasi Bulanan</h3>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 p-4 bg-slate-50 rounded-lg border border-slate-100 italic">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Jenis Pajak:</span>
                        <p class="text-sm font-bold text-slate-800">{{ $realization->taxType->name }}</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Kecamatan:</span>
                        <p class="text-sm font-bold text-slate-800">{{ $realization->district->name }}</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Tahun Anggaran:</span>
                        <p class="text-sm font-bold text-slate-800">{{ $realization->year }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($months as $month)
                        @php $fieldName = $month->column_name; @endphp
                        <div class="p-4 bg-white rounded-lg border border-slate-200 shadow-sm">
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">{{ $month->name }}</p>
                            <p class="text-base font-bold text-slate-900">
                                Rp {{ number_format($realization->$fieldName, 0, ',', '.') }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-between items-center">
                    <div class="text-[10px] text-slate-400 font-mono">
                        ID Data: #{{ $realization->id }} | Dibuat: {{ $realization->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div class="text-sm font-bold text-emerald-700">
                        Total Realisasi: Rp {{ number_format($realization->total_amount, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.employee>

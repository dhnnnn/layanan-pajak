<div id="wp-metadata" class="hidden" data-total-wp="{{ $taxPayers->total() }}"></div>
<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 text-slate-500 font-black uppercase text-[9px] tracking-widest border-b border-slate-200">
            <tr>
                <th rowspan="2" class="px-6 py-4 border-r border-slate-100 align-middle">NPWPD / Obyek</th>
                <th rowspan="2" class="px-6 py-4 border-r border-slate-100 align-middle">Alamat Wajib Pajak</th>
                <th rowspan="2" class="px-6 py-4 border-r border-slate-100 align-middle text-center">Kecamatan</th>
                @for($m = $selectedMonthFrom; $m <= $selectedMonthTo; $m++)
                    <th colspan="3" class="px-4 py-3 border-r border-slate-100 border-b border-slate-100 text-center bg-slate-100/30">
                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </th>
                @endfor
                <th rowspan="2" class="px-6 py-4 text-center align-middle">Status WP</th>
            </tr>
            <tr class="bg-slate-50/50">
                @for($m = $selectedMonthFrom; $m <= $selectedMonthTo; $m++)
                    @php
                        $colSptpd = 'sptpd_' . $m;
                        $colBayar = 'bayar_' . $m;
                        $activeSortBy = request('sort_by', '');
                        $activeSortDir = request('sort_dir', 'desc');
                    @endphp
                    <th class="px-2 py-2 text-center border-r border-slate-100 text-[8px] font-black text-slate-400">Tgl SPTPD</th>
                    <th class="px-2 py-2 text-center border-r border-slate-100 text-[8px] font-black text-slate-400 cursor-pointer hover:text-blue-600 transition-colors group select-none"
                        data-sort-col="{{ $colSptpd }}">
                        <div class="flex items-center justify-center gap-1">
                            Jml SPTPD
                            <x-sort-icon :column="$colSptpd" :active-col="$activeSortBy" :active-dir="$activeSortDir" />
                        </div>
                    </th>
                    <th class="px-2 py-2 text-center border-r border-slate-100 text-[8px] font-black text-slate-400 cursor-pointer hover:text-blue-600 transition-colors group select-none"
                        data-sort-col="{{ $colBayar }}">
                        <div class="flex items-center justify-center gap-1">
                            Jml Bayar
                            <x-sort-icon :column="$colBayar" :active-col="$activeSortBy" :active-dir="$activeSortDir" />
                        </div>
                    </th>
                @endfor
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-[11px]">
            @forelse($taxPayers as $wp)
                @php $wpKey = $wp->npwpd . '-' . $wp->nop; @endphp
                {{-- Main Row --}}
                <tr class="hover:bg-slate-50/80 transition-colors group">
                    <td class="px-6 py-4 border-r border-slate-50">
                        <div class="flex items-center gap-3">
                            <a href="{{ auth()->user()->hasRole('pegawai')
                                    ? route('field-officer.monitoring.wp-detail', [$wp->npwpd, $wp->nop, 'year' => $selectedYear, 'month_from' => $selectedMonthFrom, 'month_to' => $selectedMonthTo])
                                    : route('admin.monitoring.wp-detail', [$wp->npwpd, $wp->nop, 'year' => $selectedYear, 'month_from' => $selectedMonthFrom, 'month_to' => $selectedMonthTo]) }}"
                                title="Lihat Detail & Grafik"
                                class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-blue-600 text-slate-400 hover:text-white transition-all active:scale-95 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <div>
                                <div class="font-black text-slate-900 leading-tight uppercase group-hover:text-blue-600 transition-colors">{{ $wp->nm_wp }}</div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[9px] text-slate-400 font-mono font-bold tracking-tighter">{{ $wp->npwpd }}</span>
                                    <span class="text-[9px] text-slate-300">|</span>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase truncate max-w-[120px]">{{ $wp->tax_type_name ?? '-' }}</span>
                                </div>
                                @if(!empty($wp->nm_op))
                                    <div class="text-[9px] text-slate-300 mt-0.5 uppercase truncate max-w-[180px]">{{ $wp->nm_op }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 border-r border-slate-50 text-slate-500 font-medium uppercase text-[9px] leading-relaxed italic">
                        {{ $wp->almt_op }}
                    </td>
                    <td class="px-6 py-4 border-r border-slate-50 text-center uppercase text-slate-600 font-black text-[9px] whitespace-nowrap">
                        <span class="bg-slate-100 px-2 py-1 rounded text-slate-500">{{ $wp->nm_kecamatan }}</span>
                    </td>
                    @for($m = $selectedMonthFrom; $m <= $selectedMonthTo; $m++)
                        @php $data = $wp->monthly_data[$m]; @endphp
                        <td class="px-2 py-3 border-r border-slate-50 text-center text-slate-500 font-bold text-[10px]">
                            {{ $data['tgl_lapor'] }}
                        </td>
                        <td class="px-2 py-3 border-r border-slate-50 text-right font-black {{ $data['jml_lapor'] > 0 ? 'text-blue-600' : 'text-slate-200' }}">
                            {{ $data['jml_lapor'] > 0 ? number_format($data['jml_lapor'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-2 py-3 border-r border-slate-50 text-right font-black {{ $data['total_bayar'] > 0 ? 'text-emerald-600' : 'text-slate-200' }}">
                            {{ $data['total_bayar'] > 0 ? number_format($data['total_bayar'], 0, ',', '.') : '-' }}
                        </td>
                    @endfor
                    <td class="px-6 py-4 text-center">
                        @if($wp->status == '1')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-sm">AKTIF</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter bg-rose-50 text-rose-600 border border-rose-100 shadow-sm">NON-AKTIF</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 4 + ($selectedMonthTo - $selectedMonthFrom + 1) * 4 }}" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center justify-center space-y-4">
                            <div class="w-16 h-16 bg-slate-50 rounded-3xl flex items-center justify-center">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div class="max-w-xs mx-auto">
                                <p class="text-slate-900 font-black uppercase tracking-widest text-[11px] mb-1">Data Tidak Ditemukan</p>
                                <p class="text-slate-400 text-[10px] font-medium leading-relaxed italic">Gunakan filter yang berbeda atau cari kata kunci lain untuk menemukan Wajib Pajak yang Anda cari.</p>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($taxPayers->hasPages())
    <div class="px-6 py-5 bg-slate-50/50 border-t border-slate-100 shadow-inner ajax-pagination">
        {{ $taxPayers->withQueryString()->links() }}
    </div>
@endif

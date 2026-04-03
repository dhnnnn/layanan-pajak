<x-layouts.field-officer title="Detail WP" header="Detail Wajib Pajak">
    <x-slot:headerActions>
        <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- WP Info Card --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-800 text-sm mb-4">Informasi Wajib Pajak</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">NPWPD</p>
                    <p class="text-sm font-medium text-slate-900">{{ $wp->npwpd }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Nama WP</p>
                    <p class="text-sm font-medium text-slate-900">{{ $wp->nm_wp }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Nomor Objek Pajak</p>
                    <p class="text-sm font-medium text-slate-900">{{ $wp->nop }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Alamat Objek Pajak</p>
                    <p class="text-sm font-medium text-slate-900">{{ $wp->almt_op }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Tahun</p>
                    <p class="text-sm font-medium text-slate-900">{{ $wp->year }}</p>
                </div>
            </div>
        </div>

        {{-- Payment Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Ketetapan</p>
                <p class="text-xl font-bold text-blue-600">Rp {{ number_format($wp->total_ketetapan, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Bayar</p>
                <p class="text-xl font-bold text-emerald-600">Rp {{ number_format($wp->total_bayar, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Tunggakan</p>
                <p class="text-xl font-bold text-orange-600">Rp {{ number_format($wp->total_tunggakan, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Status</p>
                @php $isLunas = $wp->total_tunggakan <= 0; @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $isLunas ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700' }}">
                    {{ $isLunas ? 'Lunas' : 'Belum Lunas' }}
                </span>
            </div>
        </div>

        {{-- Monthly Reports Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/30">
                <h3 class="font-bold text-slate-800 text-sm italic uppercase tracking-wider">Riwayat Pembayaran per Bulan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-center">No</th>
                            <th class="px-6 py-4">Bulan</th>
                            <th class="px-6 py-4 text-right">Jumlah SPTPD</th>
                            <th class="px-6 py-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($reports as $index => $report)
                            @php
                                $bulanName = $months[$report->month] ?? 'Unknown';
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-center text-slate-500">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $bulanName }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">Rp {{ number_format($report->jml_sptpd, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                        Terbayar
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-400">
                                    <p class="text-sm italic">Tidak ada laporan SPTPD.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.field-officer>



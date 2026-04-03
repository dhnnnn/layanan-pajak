<x-layouts.field-officer title="Daftar Tunggakan WP" header="Daftar Wajib Pajak yang Memiliki Tunggakan">
    <x-slot:headerActions>
        <form action="{{ route('field-officer.monitoring.arrears') }}" method="GET" class="flex items-center gap-2">
            <select name="year" onchange="this.form.submit()" class="appearance-none bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 pr-8 hover:bg-slate-50 cursor-pointer">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>

            <select name="district_id" onchange="this.form.submit()" class="appearance-none bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 pr-8 hover:bg-slate-50 cursor-pointer">
                <option value="">Semua Kecamatan</option>
                @foreach($districts as $d)
                    <option value="{{ $d->id }}" {{ $selectedDistrictId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                @endforeach
            </select>

            <input type="text" name="search" value="{{ $search }}" placeholder="Cari NPWPD/Nama WP..." 
                class="border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 w-48">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Cari</button>
        </form>
    </x-slot:headerActions>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">NPWPD</th>
                        <th class="px-6 py-4">Nama WP</th>
                        <th class="px-6 py-4">Objek Pajak</th>
                        <th class="px-6 py-4">Alamat</th>
                        <th class="px-6 py-4 text-right">Ketetapan</th>
                        <th class="px-6 py-4 text-right">Bayar</th>
                        <th class="px-6 py-4 text-right">Tunggakan</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($taxpayers as $wp)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-slate-600 font-mono text-xs">{{ $wp->npwpd }}</td>
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $wp->nm_wp }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $wp->nop }}</td>
                            <td class="px-6 py-4 text-slate-500 max-w-xs truncate">{{ $wp->almt_op }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">Rp {{ number_format($wp->total_ketetapan, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-emerald-600 font-medium">Rp {{ number_format($wp->total_bayar, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-orange-600 font-bold">Rp {{ number_format($wp->total_tunggakan, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('field-officer.monitoring.wp-detail', $wp->npwpd) }}?year={{ $year }}" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 text-[10px] font-black uppercase tracking-wider rounded-lg transition-all border border-rose-100">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Detail WP
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-slate-400">
                                <p class="text-sm italic">Tidak ada data tunggakan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-200">
            {{ $taxpayers->links() }}
        </div>
    </div>
</x-layouts.field-officer>



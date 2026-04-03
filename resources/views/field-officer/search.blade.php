<x-layouts.field-officer title="Pencarian WP" header="Cari Wajib Pajak">
    <x-slot:headerActions>
        <form action="{{ route('pegawai.monitoring.pencarian') }}" method="GET" class="flex items-center gap-2">
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

            <input type="text" name="search" value="{{ $search }}" placeholder="Cari NPWPD/Nama WP/Alamat..." 
                class="border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 w-64">
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
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($taxpayers as $wp)
                        @php
                            $isLunas = $wp->total_tunggakan <= 0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-slate-600 font-mono text-xs">{{ $wp->npwpd }}</td>
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $wp->nm_wp }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $wp->nop }}</td>
                            <td class="px-6 py-4 text-slate-500 max-w-xs truncate">{{ $wp->almt_op }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">Rp {{ number_format($wp->total_ketetapan, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-emerald-600 font-medium">Rp {{ number_format($wp->total_bayar, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('pegawai.monitoring.wp-detail', $wp->npwpd) }}?year={{ $year }}" 
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-400">
                                <p class="text-sm italic">Ketik kata kunci untuk mencari WP.</p>
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



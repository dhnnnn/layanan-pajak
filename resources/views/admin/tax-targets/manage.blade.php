<x-layouts.admin title="Daftar Target Pajak" header="Pengelolaan Target APBD">
    <x-slot:headerActions>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.tax-targets.report', array_filter(['year' => request('year', $year)])) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Lihat Laporan
            </a>
            <a href="{{ route('admin.tax-targets.export', array_filter(['year' => request('year', $year)])) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg transition-colors shadow-sm border border-slate-200">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export Excel
            </a>
        </div>
    </x-slot:headerActions>
    <div class="space-y-6">
        <!-- Filter Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.tax-targets.manage') }}" class="p-6" id="filterForm">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <!-- Search Input -->
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-slate-700 mb-2">Cari Jenis Pajak</label>
                    <div class="relative">
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari berdasarkan nama atau kode pajak..."
                            class="w-full pl-10 pr-4 py-2 rounded-lg bg-slate-50 text-slate-700 focus:bg-white focus:ring-2 focus:ring-blue-500/20 text-sm border-0">
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Year Dropdown -->
                <div class="w-full md:w-52">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Filter Tahun</label>
                    <div class="relative" id="yearDropdownWrapper">
                        <button type="button" id="yearDropdownBtn"
                            class="w-full flex items-center justify-between px-4 py-2 rounded-lg bg-slate-50 text-slate-700 text-sm border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <span id="yearDropdownLabel">{{ request('year') ?: $year }}</span>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <input type="hidden" name="year" id="yearValue" value="{{ request('year', $year) }}">

                        <div id="yearDropdownMenu" class="hidden absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg overflow-hidden">
                            <ul id="yearList" class="max-h-48 overflow-y-auto py-1">
                                @forelse($availableYears as $availableYear)
                                    <li>
                                        <button type="button" data-value="{{ $availableYear }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 {{ request('year', $year) == $availableYear ? 'font-semibold text-blue-600' : 'text-slate-700' }}">
                                            {{ $availableYear }}
                                        </button>
                                    </li>
                                @empty
                                    <li>
                                        <button type="button" data-value="{{ date('Y') }}" class="year-option w-full text-left px-4 py-2 text-sm hover:bg-slate-50 font-semibold text-blue-600">
                                            {{ date('Y') }}
                                        </button>
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

                @if(request()->filled('search') || (request()->filled('year') && request('year') != date('Y')))
                    <a href="{{ route('admin.tax-targets.manage') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold rounded-lg transition-colors shrink-0">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filterForm');
            const searchInput = document.getElementById('search');

            // Debounced search
            let searchTimeout;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => form.submit(), 700);
            });

            // Year dropdown logic
            const btn = document.getElementById('yearDropdownBtn');
            const menu = document.getElementById('yearDropdownMenu');
            const yearValueInput = document.getElementById('yearValue');
            const yearLabel = document.getElementById('yearDropdownLabel');

            if (btn && menu) {
                btn.addEventListener('click', function () {
                    menu.classList.toggle('hidden');
                });

                document.addEventListener('click', function (e) {
                    if (!document.getElementById('yearDropdownWrapper').contains(e.target)) {
                        menu.classList.add('hidden');
                    }
                });

                document.querySelectorAll('.year-option').forEach(function (opt) {
                    opt.addEventListener('click', function () {
                        yearValueInput.value = this.dataset.value;
                        yearLabel.textContent = this.textContent.trim();
                        menu.classList.add('hidden');
                        form.submit();
                    });
                });
            }
        });
    </script>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 border-r border-slate-200" rowspan="2">Uraian Jenis Pajak</th>
                        <th class="px-4 py-3 text-center border-r border-slate-200" rowspan="2">Target APBD {{ $year }}</th>
                        <th class="px-4 py-3 text-center border-b border-r border-slate-200" colspan="4">Rincian Target Per Tribulan</th>
                        <th class="px-4 py-3 text-center" rowspan="2">Aksi</th>
                    </tr>
                    <tr class="bg-slate-50 text-[10px]">
                        <th class="px-2 py-2 text-center border-r border-slate-200">Q1</th>
                        <th class="px-2 py-2 text-center border-r border-slate-200">Q2</th>
                        <th class="px-2 py-2 text-center border-r border-slate-200">Q3</th>
                        <th class="px-2 py-2 text-center border-r border-slate-200">Q4</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($taxTypes as $item)
                        <tr class="{{ $item['is_parent'] ? 'bg-slate-50 font-bold' : 'hover:bg-slate-50' }}">
                            <td class="px-4 py-3 border-r border-slate-200">
                                <div class="flex items-center">
                                    @if(!$item['is_parent'] && $item['tax_type_parent_id'])
                                        <span class="ml-4 text-slate-300">↳</span>
                                    @endif
                                    <span class="{{ $item['is_parent'] ? 'text-slate-900' : 'text-slate-600' }}">
                                        {{ $item['tax_type_name'] }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right border-r border-slate-200 font-mono">
                                {{ number_format($item['target_total'], 0, ',', '.') }}
                            </td>
                            <td class="px-2 py-3 text-right border-r border-slate-200 font-mono text-[11px]">
                                {{ number_format($item['targets']['q1'], 0, ',', '.') }}
                            </td>
                            <td class="px-2 py-3 text-right border-r border-slate-200 font-mono text-[11px]">
                                {{ number_format($item['targets']['q2'], 0, ',', '.') }}
                            </td>
                            <td class="px-2 py-3 text-right border-r border-slate-200 font-mono text-[11px]">
                                {{ number_format($item['targets']['q3'], 0, ',', '.') }}
                            </td>
                            <td class="px-2 py-3 text-right border-r border-slate-200 font-mono text-[11px]">
                                {{ number_format($item['targets']['q4'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center items-center gap-1.5">
                                    {{-- View Detail Action --}}
                                    <a href="{{ route('admin.tax-targets.show', [$item['tax_type_id'], 'year' => $year]) }}" 
                                       class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all" title="Lihat Detail Realisasi Wajib Pajak">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    {{-- Edit / Override Action --}}
                                    @if($item['tax_target_id'])
                                        <a href="{{ route('admin.tax-targets.edit', $item['tax_target_id']) }}?year={{ $year }}" 
                                           class="p-1 text-amber-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Edit Target Override">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    @else
                                        <a href="{{ route('admin.tax-targets.create', ['tax_type_id' => $item['tax_type_id'], 'year' => $year]) }}" 
                                           class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all" title="Ubah Target Sistem (Simpadu)">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- Reset / Delete Action --}}
                                    <div class="flex items-center">
                                        @if($item['tax_target_id'])
                                            <form action="{{ route('admin.tax-targets.destroy', $item['tax_target_id']) }}" method="POST" id="reset-form-{{ $item['tax_target_id'] }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" 
                                                        class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all btn-reset-override" 
                                                        data-form-id="reset-form-{{ $item['tax_target_id'] }}"
                                                        title="Reset (Kembali ke Anggaran Simpadu)">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" 
                                                    class="p-1 text-red-400 opacity-40 hover:opacity-100 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all btn-reset-baseline" 
                                                    title="Sudah sesuai Anggaran Sistem">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500 italic">
                                Tidak ada data target untuk ditampilkan (Nilai 0 disembunyikan sesuai pengaturan Dashboard).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
    </div>
</div>

{{-- SweetAlert2 CDN and Reset Logic --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle reset for overrides
        document.querySelectorAll('.btn-reset-override').forEach(button => {
            button.addEventListener('click', function() {
                const formId = this.dataset.formId;
                const form = document.getElementById(formId);

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Override target ini akan dihapus dan nilai akan kembali ke Anggaran Sistem (Simpadu).",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Reset Target!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Handle reset for baseline items
        document.querySelectorAll('.btn-reset-baseline').forEach(button => {
            button.addEventListener('click', function() {
                Swal.fire({
                    title: 'Sudah Sesuai Baseline',
                    text: "Target ini sudah menggunakan Anggaran Sistem (Simpadu). Tidak ada pengaturan kustom yang perlu di-reset.",
                    icon: 'info',
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#64748b'
                });
            });
        });
    });
</script>
</x-layouts.admin>

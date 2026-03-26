<x-layouts.admin title="Kelola Target UPT">
    <x-slot:header>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="flex text-sm text-slate-500 mb-1" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2">
                        <li><a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600">Dashboard</a></li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span>Perbandingan UPT</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="text-slate-900 font-medium">Kelola Target UPT</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-slate-900">Kelola Target UPT</h1>
            </div>
        </div>
    </x-slot:header>

    <div class="space-y-6">

        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="p-6 border-b border-slate-100">
                <form action="{{ route('admin.upt-comparisons.manage') }}" method="GET" id="filterForm" class="flex flex-wrap items-center gap-4">
                    <div class="w-full sm:w-40">
                        <label for="year" class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Tahun</label>
                        <select name="year" id="year" onchange="document.getElementById('filterForm').submit()"
                            class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach ($availableYears as $availableYear)
                                <option value="{{ $availableYear }}" @selected($availableYear == $year)>
                                    {{ $availableYear }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full sm:w-64">
                        <label for="upt_id" class="block text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">UPT</label>
                        <select name="upt_id" id="upt_id" onchange="document.getElementById('filterForm').submit()"
                            class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach ($upts as $upt)
                                <option value="{{ $upt->id }}" @selected($upt->id == $uptId)>
                                    {{ $upt->name }} ({{ $upt->code }}) {{ $upt->has_targets ? '— (Sudah ada target)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <form action="{{ route('admin.upt-comparisons.upsert') }}" method="POST" id="targetForm">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="upt_id" value="{{ $uptId }}">

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider w-1/2">Nama Pajak</th>
                                <th class="px-6 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">Target UPT (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($taxTypes as $taxType)
                                {{-- Parent Tax Type --}}
                                @php $hasChildren = $taxType->children->isNotEmpty(); @endphp
                                <tr class="bg-slate-50/60 hover:bg-slate-100/60 transition-colors font-semibold">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-slate-900">{{ $taxType->name }}</span>
                                            @if($hasChildren)
                                                <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-[10px] rounded font-bold uppercase tracking-wider">Akumulasi subbab</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="relative max-w-xs ml-auto">
                                            @if($hasChildren)
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-blue-400 text-sm pointer-events-none font-bold">Rp</span>
                                                <div class="w-full pl-9 py-2 rounded-lg bg-blue-50/50 border border-blue-100 text-sm font-bold text-blue-700 text-right">
                                                    <span id="total-{{ $taxType->id }}">{{ number_format($targets[$taxType->id] ?? 0, 0, ',', '.') }}</span>
                                                </div>
                                            @else
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-sm pointer-events-none">Rp</span>
                                                <input type="number" 
                                                    name="targets[{{ $taxType->id }}]" 
                                                    value="{{ old('targets.' . $taxType->id, (float)($targets[$taxType->id] ?? 0)) }}"
                                                    step="0.01"
                                                    onfocus="if(this.value == '0') this.value = ''"
                                                    onblur="if(this.value == '') this.value = '0'"
                                                    onkeydown="if(['e', 'E', '+', '-'].includes(event.key)) event.preventDefault();"
                                                    class="w-full pl-9 rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500 font-semibold text-right"
                                                    placeholder="0">
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Children Tax Types --}}
                                @foreach ($taxType->children as $child)
                                    <tr class="hover:bg-slate-50 transition-colors border-l-2 border-purple-200">
                                        <td class="px-8 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="text-slate-300 text-sm">↳</span>
                                                <span class="text-sm text-slate-700 font-medium">{{ $child->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="relative max-w-xs ml-auto">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-sm pointer-events-none">Rp</span>
                                                <input type="number" 
                                                    name="targets[{{ $child->id }}]" 
                                                    data-parent="{{ $taxType->id }}"
                                                    value="{{ old('targets.' . $child->id, (float)($targets[$child->id] ?? 0)) }}"
                                                    step="0.01"
                                                    onfocus="if(this.value == '0') this.value = ''"
                                                    onblur="if(this.value == '') this.value = '0'"
                                                    onkeydown="if(['e', 'E', '+', '-'].includes(event.key)) event.preventDefault();"
                                                    class="child-input w-full pl-9 rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500 text-right"
                                                    placeholder="0">
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <a href="{{ route('admin.upt-comparisons.report') }}"
                        class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-sm">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const childInputs = document.querySelectorAll('.child-input');
            
            function formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(Math.round(num));
            }

            function updateParent(parentId) {
                const totalSpan = document.getElementById('total-' + parentId);
                if (!totalSpan) return;
                
                const siblings = document.querySelectorAll(`[data-parent="${parentId}"]`);
                let total = 0;
                siblings.forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                
                totalSpan.textContent = formatNumber(total);
            }
            
            childInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const parentId = this.getAttribute('data-parent');
                    updateParent(parentId);
                });
            });
            
            // Initial calculation for all parents
            const parentIds = new Set();
            childInputs.forEach(input => {
                const pid = input.getAttribute('data-parent');
                if (pid) parentIds.add(pid);
            });
            parentIds.forEach(id => updateParent(id));
        });
    </script>
</x-layouts.admin>

<x-layouts.admin title="Assign Wilayah UPT" header="Assign Wilayah ke {{ $upt->name }}">
    <x-slot:headerActions>
        <a href="{{ route('admin.upts.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </x-slot:headerActions>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $upt->name }}</h3>
                        <p class="text-sm text-slate-600">{{ $upt->code }}</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.upts.districts.store', $upt) }}" method="POST" class="p-6">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-3">Pilih Wilayah <span class="text-red-500">*</span></label>
                    <p class="text-xs text-slate-500 mb-4">Pilih kecamatan yang termasuk dalam wilayah kerja UPT ini.</p>
                    
                    <div class="space-y-2 max-h-96 overflow-y-auto border border-slate-200 rounded-lg p-4">
                        @foreach($districts as $district)
                            <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors border border-transparent hover:border-slate-200">
                                <input type="checkbox" name="district_ids[]" value="{{ $district->id }}" 
                                    {{ in_array($district->id, $assignedDistrictIds) ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <div class="flex-1">
                                    <span class="font-medium text-slate-900">{{ $district->name }}</span>
                                    <span class="ml-2 text-xs font-mono text-slate-500 bg-slate-100 px-2 py-0.5 rounded">{{ $district->code }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    
                    @error('district_ids')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-sm text-slate-600">
                        <span class="font-semibold" id="selected-count">{{ count($assignedDistrictIds) }}</span> wilayah dipilih
                    </p>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan Wilayah
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="district_ids[]"]');
            const counter = document.getElementById('selected-count');
            
            function updateCounter() {
                const checked = document.querySelectorAll('input[name="district_ids[]"]:checked').length;
                counter.textContent = checked;
            }
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCounter);
            });
        });
    </script>
</x-layouts.admin>

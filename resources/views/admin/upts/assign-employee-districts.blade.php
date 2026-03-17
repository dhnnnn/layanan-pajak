<x-layouts.admin title="Atur Wilayah Tugas" :header="'Atur Wilayah: ' . $employee->name">
    <x-slot:headerActions>
        <a href="{{ route('admin.upts.show', $upt) }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Detail UPT
        </a>
    </x-slot:headerActions>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $employee->name }}</h3>
                        <p class="text-sm text-slate-500">{{ $employee->email }} &mdash; {{ $upt->name }}</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.employees.districts.assign', $employee) }}" method="POST" class="p-6">
                @csrf

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-semibold text-slate-700">Pilih Wilayah Tugas</label>
                        <p class="text-xs text-slate-500">
                            <span class="font-semibold" id="selected-count">{{ $assignedDistrictIds->count() }}</span> wilayah dipilih
                        </p>
                    </div>
                    <p class="text-xs text-slate-500 mb-4">Hanya kecamatan dalam cakupan {{ $upt->name }} yang ditampilkan.</p>

                    <div class="space-y-2 border border-slate-200 rounded-lg p-4">
                        @forelse($upt->districts as $district)
                            <label class="flex items-center gap-3 p-3 border border-transparent rounded-lg hover:border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                                <input
                                    type="checkbox"
                                    name="district_ids[]"
                                    value="{{ $district->id }}"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    {{ $assignedDistrictIds->contains($district->id) ? 'checked' : '' }}>
                                <span class="text-sm font-medium text-slate-900">{{ $district->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500 text-center py-4">Belum ada wilayah di UPT ini.</p>
                        @endforelse
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.upts.show', $upt) }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan Wilayah
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('input[name="district_ids[]"]');
            const counter = document.getElementById('selected-count');

            function updateCounter() {
                counter.textContent = document.querySelectorAll('input[name="district_ids[]"]:checked').length;
            }

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', updateCounter);
            });
        });
    </script>
</x-layouts.admin>

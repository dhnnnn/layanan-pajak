<x-layouts.admin title="Detail UPT" :header="'Detail UPT: ' . $upt->name">
    <x-slot:headerActions>
        <a href="{{ route('admin.upts.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </x-slot:headerActions>

    <div class="space-y-6">
        {{-- UPT Info --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi UPT</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase">Nama UPT</label>
                    <p class="text-sm font-medium text-slate-900 mt-1">{{ $upt->name }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase">Kode</label>
                    <p class="text-sm font-mono text-slate-900 mt-1">{{ $upt->code }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase">Deskripsi</label>
                    <p class="text-sm text-slate-900 mt-1">{{ $upt->description ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Wilayah Cakupan UPT --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Wilayah Cakupan UPT</h3>
                <a href="{{ route('admin.upts.districts', $upt) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    Kelola Wilayah
                </a>
            </div>
            <div class="p-6">
                @if($upt->districts->count() > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($upt->districts as $district)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                {{ $district->name }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500 italic">Belum ada wilayah yang ditugaskan ke UPT ini.</p>
                @endif
            </div>
        </div>

        {{-- Pegawai dan Wilayah Mereka --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="font-bold text-slate-800">Pegawai dan Wilayah Tugasnya</h3>
                <p class="text-xs text-slate-500 mt-1">Setiap pegawai dapat ditugaskan ke kecamatan dalam cakupan UPT ini</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Nama Pegawai</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Wilayah Tugas</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($upt->users as $user)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900">
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $user->email }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->districts->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user->districts as $district)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-50 text-green-700 rounded text-xs">
                                                    {{ $district->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Belum ada wilayah</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        onclick="openAssignModal({{ $user->id }}, '{{ $user->name }}', {{ $user->districts->pluck('id') }})"
                                        class="text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                        Atur Wilayah
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                    <p>Belum ada pegawai di UPT ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Assign Wilayah --}}
    <div id="assignModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Atur Wilayah Tugas</h3>
                <button onclick="closeAssignModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="assignForm" method="POST" action="">
                @csrf
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <p class="text-sm text-slate-600 mb-4">
                        Pegawai: <span id="modalEmployeeName" class="font-semibold text-slate-900"></span>
                    </p>
                    
                    <div class="space-y-2">
                        @foreach($upt->districts as $district)
                            <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors">
                                <input 
                                    type="checkbox" 
                                    name="district_ids[]" 
                                    value="{{ $district->id }}"
                                    class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500"
                                    {{ in_array($district->id, $assignedToOtherUpts) ? 'disabled' : '' }}>
                                <div class="flex-1">
                                    <span class="text-sm font-medium text-slate-900">{{ $district->name }}</span>
                                    @if(in_array($district->id, $assignedToOtherUpts))
                                        <span class="ml-2 text-xs text-red-600">(Sudah ditugaskan ke UPT lain)</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeAssignModal()" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal(userId, userName, currentDistricts) {
            document.getElementById('modalEmployeeName').textContent = userName;
            document.getElementById('assignForm').action = `/admin/employees/${userId}/districts`;
            
            // Reset all checkboxes
            document.querySelectorAll('input[name="district_ids[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check current districts
            currentDistricts.forEach(districtId => {
                const checkbox = document.querySelector(`input[name="district_ids[]"][value="${districtId}"]`);
                if (checkbox) checkbox.checked = true;
            });
            
            document.getElementById('assignModal').classList.remove('hidden');
        }
        
        function closeAssignModal() {
            document.getElementById('assignModal').classList.add('hidden');
        }
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeAssignModal();
        });
        
        // Close modal on backdrop click
        document.getElementById('assignModal').addEventListener('click', function(e) {
            if (e.target === this) closeAssignModal();
        });
    </script>
</x-layouts.admin>

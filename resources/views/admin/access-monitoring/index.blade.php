<x-layouts.admin title="Monitoring Akses" header="Monitoring Akses per Role">

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4 min-w-[160px]">Role</th>
                        <th class="px-6 py-4 text-center">Total Permission</th>
                        <th class="px-6 py-4 text-center">User</th>
                        @foreach($groups as $group)
                            <th class="px-4 py-4 text-center whitespace-nowrap">{{ $group }}</th>
                        @endforeach
                        <th class="px-6 py-4 text-right">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($roles as $role)
                        @php
                            $rolePermissionGroups = $role->permissions->pluck('group')->unique()->toArray();
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $role->name }}</div>
                                @if($role->isSystemRole())
                                    <span class="text-[10px] text-amber-600 font-bold uppercase">Sistem</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-700 text-xs font-bold">
                                    {{ $role->permissions->count() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-slate-500 text-xs font-medium">
                                {{ $role->users_count }}
                            </td>
                            @foreach($groups as $group)
                                <td class="px-4 py-4 text-center">
                                    @if(in_array($group, $rolePermissionGroups))
                                        <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-slate-200 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.access-monitoring.show', $role) }}"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>

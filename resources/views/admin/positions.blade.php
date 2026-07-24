@extends('layouts.admin')
@section('page-title', 'Positions Management')
@section('page-subtitle', 'Define the electoral positions for this election.')
@php $activeView = 'positions'; @endphp

@section('content')

@if($election)
<div class="mb-4 px-4 py-3 rounded-xl text-sm flex items-center justify-between
    {{ $election->isEditable() ? 'bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-gold-bright' : 'bg-gray-100 dark:bg-white/10 border border-gray-200 dark:border-white/10 text-gray-600 dark:text-white/70' }}">
    <span>Managing: <strong>{{ $election->title }}</strong> ({{ $election->status }})
        @if(!$election->isEditable()) — read-only, this election is closed. @endif
    </span>
    <a href="{{ route('admin.elections.index') }}" class="text-xs font-semibold underline">Switch election</a>
</div>
@else
<div class="mb-4 px-4 py-3 rounded-xl text-sm bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800/40 text-yellow-800 dark:text-yellow-300">
    No election exists yet. <a href="{{ route('admin.elections.index') }}" class="underline font-semibold">Create one</a> before adding positions.
</div>
@endif

<div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm border border-gray-100 dark:border-white/10 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
        <h2 class="font-bold text-primary dark:text-parchment">Electoral Positions</h2>
        @if($election && $election->isEditable())
        <button onclick="openModal()"
            class="bg-primary hover:bg-secondary text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow">
            + Add Position
        </button>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">#</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Position Name</th>
                    <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Max Votes</th>
                    <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                @forelse($positions as $position)
                <tr class="hover:bg-gray-50 hover:dark:bg-white/5 transition-colors" id="pos-row-{{ $position->id }}">
                    <td class="px-6 py-4 text-xs text-gray-400 dark:text-white/40">{{ $position->id }}</td>
                    <td class="px-6 py-4 font-medium text-sm text-primary dark:text-parchment">{{ $position->name }}</td>
                    <td class="px-6 py-4 text-center text-sm">{{ $position->max_votes }} vote{{ $position->max_votes > 1 ? 's' : '' }}</td>
                    <td class="px-6 py-4 text-center space-x-3">
                        @if($election && $election->isEditable())
                        <button onclick="openModal({{ json_encode(['id'=>$position->id,'name'=>$position->name,'max_votes'=>$position->max_votes]) }})"
                            class="text-blue-600 dark:text-gold-bright hover:text-blue-800 hover:dark:text-gold-bright text-xs font-semibold">Edit</button>
                        <button onclick="deletePosition({{ $position->id }}, '{{ addslashes($position->name) }}')"
                            class="text-red-500 dark:text-red-400 hover:text-red-700 hover:dark:text-red-300 text-xs font-semibold">Delete</button>
                        @else
                        <span class="text-xs text-gray-400 dark:text-white/40">read-only</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 dark:text-white/40 text-sm">No positions defined yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal --}}
<div id="pos-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10">
            <h3 class="font-bold text-primary dark:text-parchment" id="modal-title">Add Position</h3>
            <button onclick="closeModal()" class="text-gray-400 dark:text-white/40 hover:text-gray-600 hover:dark:text-white/70">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="pos-id">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Position Name</label>
                <input type="text" id="p-name" class="bg-white dark:bg-white/10 text-gray-800 dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary" placeholder="e.g. SSG President">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Max Votes Allowed</label>
                <input type="number" id="p-max_votes" min="1" max="10" value="1" class="bg-white dark:bg-white/10 text-gray-800 dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary">
            </div>
            <p id="modal-error" class="text-red-600 dark:text-red-400 text-xs hidden"></p>
        </div>
        <div class="flex justify-end gap-3 p-6 border-t border-gray-100 dark:border-white/10">
            <button onclick="closeModal()" class="px-4 py-2 text-sm text-gray-600 dark:text-white/70 border border-gray-300 dark:border-white/20 rounded-xl">Cancel</button>
            <button onclick="savePosition()" class="px-5 py-2 text-sm bg-primary hover:bg-secondary text-white font-semibold rounded-xl transition">Save</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openModal(data = null) {
    document.getElementById('pos-modal').classList.remove('hidden');
    document.getElementById('modal-title').textContent = data ? 'Edit Position' : 'Add Position';
    document.getElementById('pos-id').value       = data?.id ?? '';
    document.getElementById('p-name').value       = data?.name ?? '';
    document.getElementById('p-max_votes').value  = data?.max_votes ?? 1;
    document.getElementById('modal-error').classList.add('hidden');
}
function closeModal() { document.getElementById('pos-modal').classList.add('hidden'); }

async function savePosition() {
    const id      = document.getElementById('pos-id').value;
    const payload = { name: document.getElementById('p-name').value.trim(), max_votes: document.getElementById('p-max_votes').value };
    const url     = id ? `/admin/positions/${id}` : '{{ route("admin.positions.store") }}';
    const method  = id ? 'PUT' : 'POST';
    const res     = await apiFetch(url, { method, body: JSON.stringify(payload) });
    if (res.success) { closeModal(); location.reload(); }
    else {
        const err = document.getElementById('modal-error');
        err.textContent = res.message || 'Error saving.';
        err.classList.remove('hidden');
    }
}

async function deletePosition(id, name) {
    if (!(await confirmDialog(`Delete position "${name}"? This will also delete all associated candidates and votes.`, { danger: true }))) return;
    const res = await apiFetch(`/admin/positions/${id}`, { method: 'DELETE' });
    if (res.success) document.getElementById(`pos-row-${id}`)?.remove();
    else await alertDialog(res.message, { danger: true });
}
</script>
@endpush
@endsection

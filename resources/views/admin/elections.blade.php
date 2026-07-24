@extends('layouts.admin')
@section('page-title', 'Elections')
@section('page-subtitle', 'Create new elections and manage past ones — full history is kept.')
@php $activeView = 'elections'; @endphp

@section('content')
<div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm border border-gray-100 dark:border-white/10 overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
        <h2 class="font-bold text-primary dark:text-parchment">All Elections</h2>
        <button onclick="openCreateModal()"
            class="bg-primary hover:bg-secondary text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow">
            + New Election
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Schedule</th>
                    <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Positions / Candidates / Votes</th>
                    <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                @forelse($elections as $e)
                <tr class="hover:bg-gray-50 hover:dark:bg-white/5 transition-colors {{ $current && $current->id === $e->id ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}" id="election-row-{{ $e->id }}">
                    <td class="px-6 py-4">
                        <span class="font-medium text-sm text-primary dark:text-parchment">{{ $e->title }}</span>
                        @if($current && $current->id === $e->id)
                            <span class="ml-2 inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-gold-bright">managing</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                            {{ $e->status === 'open' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : ($e->status === 'closed' ? 'bg-gray-200 dark:bg-white/10 text-gray-600 dark:text-white/70' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300') }}">
                            {{ $e->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-white/50">
                        {{ $e->start_time ? $e->start_time->format('M j, Y g:i A') : '—' }}
                        <br>to {{ $e->end_time ? $e->end_time->format('M j, Y g:i A') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-center text-xs text-gray-500 dark:text-white/50">
                        {{ $e->positions_count }} / {{ $e->candidates_count }} / {{ $e->votes_count }}
                    </td>
                    <td class="px-6 py-4 text-center space-x-2 whitespace-nowrap">
                        @if(!($current && $current->id === $e->id))
                        <button onclick="switchElection({{ $e->id }})" class="text-blue-600 dark:text-gold-bright hover:text-blue-800 hover:dark:text-gold-bright text-xs font-semibold">Manage</button>
                        @endif
                        @if($e->status === 'draft')
                        <button onclick="editElection({{ json_encode(['id'=>$e->id,'title'=>$e->title,'start_time'=>optional($e->start_time)->format('Y-m-d\TH:i'),'end_time'=>optional($e->end_time)->format('Y-m-d\TH:i')]) }})" class="text-blue-600 dark:text-gold-bright hover:text-blue-800 hover:dark:text-gold-bright text-xs font-semibold">Edit</button>
                        <button onclick="openElection({{ $e->id }}, '{{ addslashes($e->title) }}')" class="text-green-600 dark:text-green-400 hover:text-green-800 hover:dark:text-green-300 text-xs font-semibold">Open</button>
                        @endif
                        @if($e->status === 'open')
                        <button onclick="closeElection({{ $e->id }}, '{{ addslashes($e->title) }}')" class="text-orange-600 hover:text-orange-800 text-xs font-semibold">Close</button>
                        @endif
                        @if($e->votes_count == 0)
                        <button onclick="deleteElection({{ $e->id }}, '{{ addslashes($e->title) }}')" class="text-red-500 dark:text-red-400 hover:text-red-700 hover:dark:text-red-300 text-xs font-semibold">Delete</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400 dark:text-white/40 text-sm">No elections yet. Create your first one to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="text-xs text-gray-400 dark:text-white/40 max-w-2xl">
    Only one election can be <strong>open</strong> (votable) at a time. Closing an election makes its positions,
    candidates, and results permanently read-only history — voters will no longer see it, but nothing is deleted.
    "Manage" switches which election the Positions and Candidates pages act on.
</div>

{{-- Create / Edit Modal --}}
<div id="election-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10">
            <h3 class="font-bold text-primary dark:text-parchment" id="election-modal-title">New Election</h3>
            <button onclick="closeModal()" class="text-gray-400 dark:text-white/40 hover:text-gray-600 hover:dark:text-white/70">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="e-id">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Title</label>
                <input type="text" id="e-title" class="bg-white dark:bg-white/10 text-gray-800 dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary" placeholder="e.g. SSG Election 2026-2027">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Start Time</label>
                <input type="datetime-local" id="e-start" class="bg-white dark:bg-white/10 text-gray-800 dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">End Time</label>
                <input type="datetime-local" id="e-end" class="bg-white dark:bg-white/10 text-gray-800 dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary">
            </div>
            <p id="election-modal-error" class="text-red-600 dark:text-red-400 text-xs hidden"></p>
        </div>
        <div class="flex justify-end gap-3 p-6 border-t border-gray-100 dark:border-white/10">
            <button onclick="closeModal()" class="px-4 py-2 text-sm text-gray-600 dark:text-white/70 border border-gray-300 dark:border-white/20 rounded-xl">Cancel</button>
            <button onclick="saveElection()" class="px-5 py-2 text-sm bg-primary hover:bg-secondary text-white font-semibold rounded-xl transition">Save</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('election-modal').classList.remove('hidden');
    document.getElementById('election-modal-title').textContent = 'New Election';
    document.getElementById('e-id').value = '';
    document.getElementById('e-title').value = '';
    document.getElementById('e-start').value = '';
    document.getElementById('e-end').value = '';
    document.getElementById('election-modal-error').classList.add('hidden');
}
function editElection(data) {
    document.getElementById('election-modal').classList.remove('hidden');
    document.getElementById('election-modal-title').textContent = 'Edit Election';
    document.getElementById('e-id').value = data.id;
    document.getElementById('e-title').value = data.title;
    document.getElementById('e-start').value = data.start_time ?? '';
    document.getElementById('e-end').value = data.end_time ?? '';
    document.getElementById('election-modal-error').classList.add('hidden');
}
function closeModal() { document.getElementById('election-modal').classList.add('hidden'); }

async function saveElection() {
    const id = document.getElementById('e-id').value;
    const payload = {
        title: document.getElementById('e-title').value.trim(),
        start_time: document.getElementById('e-start').value || null,
        end_time: document.getElementById('e-end').value || null,
    };
    const url = id ? `/admin/elections/${id}` : '{{ route("admin.elections.store") }}';
    const method = id ? 'PUT' : 'POST';
    const res = await apiFetch(url, { method, body: JSON.stringify(payload) });
    if (res.success) { location.reload(); }
    else {
        const err = document.getElementById('election-modal-error');
        err.textContent = res.message || 'Error saving.';
        err.classList.remove('hidden');
    }
}

async function switchElection(id) {
    const res = await apiFetch(`/admin/elections/${id}/switch`, { method: 'POST' });
    if (res.success) location.reload();
}

async function openElection(id, title) {
    if (!(await confirmDialog(`Open "${title}" for voting? Voters will immediately see it on their dashboard.`))) return;
    const res = await apiFetch(`/admin/elections/${id}/open`, { method: 'POST' });
    if (res.success) location.reload();
    else await alertDialog(res.message, { danger: true });
}

async function closeElection(id, title) {
    if (!(await confirmDialog(`Close "${title}"? Its results and candidates will become permanent, read-only history.`, { danger: true }))) return;
    const res = await apiFetch(`/admin/elections/${id}/close`, { method: 'POST' });
    if (res.success) location.reload();
    else await alertDialog(res.message, { danger: true });
}

async function deleteElection(id, title) {
    if (!(await confirmDialog(`Delete "${title}" entirely? This cannot be undone.`, { danger: true }))) return;
    const res = await apiFetch(`/admin/elections/${id}`, { method: 'DELETE' });
    if (res.success) document.getElementById(`election-row-${id}`)?.remove();
    else await alertDialog(res.message, { danger: true });
}
</script>
@endpush
@endsection

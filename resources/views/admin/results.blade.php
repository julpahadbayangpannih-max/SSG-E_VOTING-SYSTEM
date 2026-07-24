@extends('layouts.admin')
@section('page-title', 'Live Election Results')
@section('page-subtitle', 'Real-time vote tabulation.')
@php $activeView = 'results'; @endphp

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-2">
        <label class="text-xs font-semibold text-gray-500 dark:text-white/50 uppercase tracking-wide">Election</label>
        <select id="election-select" onchange="location.href='{{ route('admin.results.index') }}?election=' + this.value"
            class="bg-white dark:bg-white/10 text-gray-800 dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2 focus:outline-none focus:ring-2 focus:ring-secondary">
            @forelse($elections as $e)
                <option value="{{ $e->id }}" {{ $election && $election->id === $e->id ? 'selected' : '' }}>
                    {{ $e->title }} ({{ $e->status }})
                </option>
            @empty
                <option>No elections yet</option>
            @endforelse
        </select>
    </div>
</div>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <p class="text-xs text-gray-400 dark:text-white/40">Auto-refreshes every 10 seconds.</p>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.results.export.csv') }}?election={{ $election?->id }}"
           class="border border-primary text-primary hover:bg-primary hover:text-white dark:border-white/20 dark:text-white dark:hover:bg-white/10 text-sm font-semibold px-4 py-2 rounded-xl transition-colors flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" />
            </svg>
            Export CSV
        </a>
        <a href="{{ route('admin.results.export.pdf') }}?election={{ $election?->id }}" target="_blank"
           class="border border-primary text-primary hover:bg-primary hover:text-white dark:border-white/20 dark:text-white dark:hover:bg-white/10 text-sm font-semibold px-4 py-2 rounded-xl transition-colors flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
            </svg>
            Print / PDF
        </a>
        @if($election && !$election->isClosed())
        <button onclick="confirmReset()"
            class="bg-red-600 dark:bg-red-600 hover:bg-red-700 hover:dark:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
            </svg>
            Reset All Votes
        </button>
        @endif
    </div>
</div>

@if($election && $election->isResultsLocked())
<div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/40 rounded-xl p-4 mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2 text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-green-600 dark:text-green-400">
            <path fill-rule="evenodd" d="M16.403 12.652a3 3 0 000-5.304 3 3 0 00-3.75-3.751 3 3 0 00-5.305 0 3 3 0 00-3.751 3.75 3 3 0 000 5.305 3 3 0 003.75 3.751 3 3 0 005.305 0 3 3 0 003.751-3.75zm-2.546-4.46a.75.75 0 00-1.214-.883l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
        </svg>
        <span class="text-green-700 dark:text-green-300 font-semibold">Results certified</span>
        <span class="text-green-600 dark:text-green-400">— {{ $election->merkle_leaf_count }} ballot(s) sealed into the Merkle root on {{ $election->results_locked_at->format('M j, Y g:i A') }}.</span>
    </div>
    <span class="font-mono text-xs text-green-700 dark:text-green-300 bg-white dark:bg-[#0f2a4a] border border-green-200 dark:border-green-800/40 rounded-lg px-2 py-1" title="{{ $election->merkle_root }}">
        {{ substr($election->merkle_root, 0, 10) }}…{{ substr($election->merkle_root, -10) }}
    </span>
</div>
@endif

<div id="results-container" class="space-y-6">
    <div class="text-center text-gray-400 dark:text-white/40 py-12">Loading results…</div>
</div>

{{-- Chart.js is only needed on this admin page, not on every page in the
     app — see layouts/app.blade.php, where it used to be loaded globally
     from an unpinned CDN. Vendored locally (public/vendor/chartjs) so the
     version is pinned and there's no third-party runtime dependency. --}}
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart-4.5.1.umd.min.js') }}"></script>
@endpush

@push('scripts')
<script>
const resultsElectionId = {{ $election?->id ?? 'null' }};

async function loadResults() {
    if (!resultsElectionId) {
        document.getElementById('results-container').innerHTML = '<div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow p-8 text-center text-gray-400 dark:text-white/40">No election selected.</div>';
        return;
    }
    try {
        const response = await fetch('{{ route("admin.results.index") }}?election=' + resultsElectionId, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const res = await response.json();
        if (!res.success) return;

        const container = document.getElementById('results-container');

        if (!res.data || res.data.length === 0) {
            container.innerHTML = '<div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow p-8 text-center text-gray-400 dark:text-white/40">No votes recorded yet.</div>';
            return;
        }

        const grouped = {};
        res.data.forEach(r => {
            if (!grouped[r.positionId]) {
                grouped[r.positionId] = { positionName: r.positionName, maxVotes: r.maxVotes, candidates: [] };
            }
            grouped[r.positionId].candidates.push(r);
        });

        container.innerHTML = '';
        Object.values(grouped).forEach(group => {
            const { positionName, maxVotes, candidates } = group;
            const sorted = [...candidates].sort((a, b) => b.voteCount - a.voteCount);
            const topCount = sorted[0]?.voteCount ?? 0;

            const rows = sorted.map((c, i) => {
                const isLeader = c.voteCount > 0 && c.voteCount === topCount;
                // SECURITY: candidateName/partyList are admin-entered and
                // only validated as plain strings server-side (no HTML
                // stripping) — escape before interpolating into innerHTML.
                const safeName  = escapeHtml(c.candidateName);
                const safeParty = escapeHtml(c.partyList || 'Independent');
                const avatar   = c.image
                    ? `<img src="${escapeHtml(c.image)}" class="h-8 w-8 rounded-full object-cover mr-3 inline-block border border-gray-200 dark:border-white/10">`
                    : `<div class="h-8 w-8 rounded-full bg-gray-200 dark:bg-white/10 inline-flex items-center justify-center text-gray-600 dark:text-white/70 font-bold mr-3 text-xs">${escapeHtml(c.candidateName.charAt(0))}</div>`;
                return `
                <tr class="${isLeader ? 'bg-yellow-50 dark:bg-yellow-900/20' : i % 2 === 0 ? 'bg-white dark:bg-[#0f2a4a]' : 'bg-gray-50 dark:bg-white/5'} hover:bg-blue-50 hover:dark:bg-blue-900/30 transition-colors">
                    <td class="px-4 py-3 text-center text-sm font-medium text-gray-500 dark:text-white/50">${i + 1}</td>
                    <td class="px-4 py-3 flex items-center text-sm">${avatar}<span class="font-medium text-primary dark:text-parchment">${safeName}</span></td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-white/50">${safeParty}</td>
                    <td class="px-4 py-3 text-center text-2xl font-extrabold text-primary dark:text-parchment">${c.voteCount}</td>
                    <td class="px-4 py-3 text-center">${isLeader ? '<span class="text-xs font-bold text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">LEADER</span>' : ''}</td>
                </tr>`;
            }).join('');

            const positionId = sorted[0]?.positionId;
            const chartId = `chart-position-${positionId}`;

            container.innerHTML += `
            <div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm border border-gray-100 dark:border-white/10 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
                    <h3 class="font-bold text-primary dark:text-parchment text-lg">${escapeHtml(positionName)}</h3>
                    <span class="text-xs bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-white/70 px-3 py-1 rounded-full">Max ${maxVotes} winner${maxVotes > 1 ? 's' : ''}</span>
                </div>
                <div class="px-6 pt-5 pb-2 h-56">
                    <canvas id="${chartId}"></canvas>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-white/10 text-xs font-bold text-primary dark:text-parchment uppercase tracking-widest">
                                <th class="px-4 py-3 text-center w-12">Rank</th>
                                <th class="px-4 py-3 text-left">Candidate</th>
                                <th class="px-4 py-3 text-left">Party</th>
                                <th class="px-4 py-3 text-center w-24">Votes</th>
                                <th class="px-4 py-3 text-center w-28">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-white/5">${rows}</tbody>
                    </table>
                </div>
            </div>`;

            // Draw/redraw the bar chart for this position after the canvas exists in the DOM
            requestAnimationFrame(() => {
                const ctx = document.getElementById(chartId);
                if (!ctx) return;
                if (positionCharts[positionId]) positionCharts[positionId].destroy();
                positionCharts[positionId] = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: sorted.map(c => c.candidateName),
                        datasets: [{
                            label: 'Votes',
                            data: sorted.map(c => c.voteCount),
                            backgroundColor: sorted.map(c => c.voteCount === topCount && topCount > 0 ? '#DAA520' : '#001f3f'),
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 } },
                            y: { grid: { display: false } },
                        },
                    },
                });
            });
        });
    } catch (e) {
        console.error('Failed to load results:', e);
    }
}

// Keep chart instances keyed by positionId so we can destroy them before
// redrawing on every 10-second auto-refresh (avoids memory leaks / ghost charts)
const positionCharts = {};

async function confirmReset() {
    if (!(await confirmDialog('RESET ALL VOTES for this election? This cannot be undone.', { title: 'Reset All Votes', danger: true }))) return;
    try {
        const response = await fetch('{{ route("admin.results.reset") }}?election=' + resultsElectionId, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        const res = await response.json();
        await alertDialog(res.success ? res.message : 'Error: ' + res.message, { danger: !res.success });
        if (res.success) loadResults();
    } catch (e) {
        await alertDialog('Failed to reset votes.', { danger: true });
    }
}

loadResults();
setInterval(loadResults, 10000);
</script>
@endpush
@endsection

@extends('layouts.admin')
@section('page-title', 'Admin Dashboard')
@section('page-subtitle', 'Welcome to the JRMSU Siocon SSG E-Voting System.')
@php $activeView = 'dashboard'; @endphp

@section('content')

{{-- Stat cards --}}
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8 stagger-group">
    @php
    $usersIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6"><path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z"/></svg>';
    $clockIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $checkCircleIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 7.53l-4.75 4.75a.75.75 0 01-1.06 0l-2.25-2.25a.75.75 0 111.06-1.06l1.72 1.72 4.22-4.22a.75.75 0 111.06 1.06z" clip-rule="evenodd"/></svg>';
    $megaphoneIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c2.5.223 4.957.702 7.324 1.417a.75.75 0 001.001-.752 24.55 24.55 0 010-8.526.75.75 0 00-1.001-.752A24.665 24.665 0 0110.34 6.66m0 9.18V16.5c0 1.35-1.164 2.4-2.5 2.4s-2.5-1.05-2.5-2.4v-.585m5-8.135V6.75m0 12.75a2.25 2.25 0 002.25-2.25v-1.53"/></svg>';
    $clipboardIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6 12h.008v.008H6V12zm0 3h.008v.008H6V15zm0 3h.008v.008H6V18z"/></svg>';
    $checkBadgeIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 000-5.304 3 3 0 00-3.75-3.751 3 3 0 00-5.305 0 3 3 0 00-3.751 3.75 3 3 0 000 5.305 3 3 0 003.75 3.751 3 3 0 005.305 0 3 3 0 003.751-3.75zm-2.546-4.46a.75.75 0 00-1.214-.883l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>';

    $cards = [
        ['label'=>'Approved Voters',  'value'=>$stats['total_voters'],     'color'=>'border-primary',    'icon'=>$usersIcon,        'route'=>route('admin.voters.index')],
        ['label'=>'Pending Approval', 'value'=>$stats['pending_voters'],   'color'=>'border-secondary',  'icon'=>$clockIcon,        'route'=>route('admin.voters.index')],
        ['label'=>'Voted',            'value'=>$stats['voted_count'],      'color'=>'border-primary',    'icon'=>$checkCircleIcon,  'route'=>route('admin.voters.index')],
        ['label'=>'Candidates',       'value'=>$stats['total_candidates'], 'color'=>'border-primary',    'icon'=>$megaphoneIcon,    'route'=>route('admin.candidates.index')],
        ['label'=>'Positions',        'value'=>$stats['total_positions'],  'color'=>'border-primary',    'icon'=>$clipboardIcon,    'route'=>route('admin.positions.index')],
        ['label'=>'Total Votes Cast', 'value'=>$stats['total_votes'],      'color'=>'border-primary',    'icon'=>$checkBadgeIcon,   'route'=>route('admin.results.index')],
    ];
    @endphp
    @foreach($cards as $i => $card)
    <a href="{{ $card['route'] }}" class="block bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm dark:shadow-black/30 p-5 border-l-4 {{ $card['color'] }} dark:border-secondary hover:-translate-y-1 hover:shadow-lg transition-all duration-300 ease-out">
        <div class="text-primary/70 dark:text-gold-bright mb-1">{!! $card['icon'] !!}</div>
        <div class="text-3xl font-extrabold text-primary dark:text-parchment stat-value" data-target="{{ $card['value'] }}" id="stat-card-{{ $i }}">0</div>
        <div class="text-xs text-gray-500 dark:text-white/50 mt-1">{{ $card['label'] }}</div>
    </a>
    @endforeach
</div>

{{-- Voter Turnout Bar --}}
<div class="foil-edge bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm dark:shadow-black/30 p-6 mb-6 border border-parchment-line dark:border-white/10">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-bold text-primary dark:text-parchment flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
            Voter Turnout
        </h2>
        <span class="text-2xl font-extrabold text-primary dark:text-gold-bright">{{ $stats['turnout_percent'] }}%</span>
    </div>
    <div class="w-full bg-gray-100 dark:bg-white/10 rounded-full h-5 overflow-hidden">
        <div class="h-5 rounded-full transition-all duration-700
            {{ $stats['turnout_percent'] >= 70 ? 'bg-green-500' : ($stats['turnout_percent'] >= 40 ? 'bg-yellow-400' : 'bg-red-400') }}"
            style="width: {{ $stats['turnout_percent'] }}%">
        </div>
    </div>
    <p class="text-xs text-gray-400 dark:text-white/50 mt-2">{{ $stats['voted_count'] }} out of {{ $stats['total_voters'] }} approved voters have cast their ballot.</p>
</div>

{{-- Turnout by Course --}}
<div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm dark:shadow-black/30 p-6 mb-6 border border-parchment-line dark:border-white/10">
    <h2 class="text-lg font-bold text-primary dark:text-parchment mb-4 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
        </svg>
        Turnout by Course
    </h2>
    @if($turnoutByCourse->isEmpty())
        <p class="text-sm text-gray-400 dark:text-white/40 text-center py-8">No approved voters yet.</p>
    @else
        <div class="h-72">
            <canvas id="turnoutByCourseChart"></canvas>
        </div>
    @endif
</div>

{{-- Currently managed election --}}
<div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm dark:shadow-black/30 p-6 mb-8 border border-parchment-line dark:border-white/10">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-primary dark:text-parchment flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Currently Managing
        </h2>
        <a href="{{ route('admin.elections.index') }}" class="text-xs font-semibold text-secondary dark:text-gold-bright hover:text-primary dark:hover:text-white">Manage Elections →</a>
    </div>
    @if($election)
        <div class="grid sm:grid-cols-3 gap-4 mb-4 text-sm">
            <div>
                <span class="block text-xs font-semibold text-gray-500 dark:text-white/40 uppercase tracking-wide mb-1">Election</span>
                <span class="font-semibold text-primary dark:text-parchment">{{ $election->title }}</span>
                <span class="ml-2 inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                    {{ $election->status === 'open' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : ($election->status === 'closed' ? 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-white/60' : 'pulse-attention bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300') }}">
                    {{ $election->status }}
                </span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-500 dark:text-white/40 uppercase tracking-wide mb-1">Start</span>
                <span class="text-primary dark:text-parchment">{{ $start ? \Carbon\Carbon::parse($start)->format('M j, Y g:i A') : '—' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-500 dark:text-white/40 uppercase tracking-wide mb-1">End</span>
                <span class="text-primary dark:text-parchment">{{ $end ? \Carbon\Carbon::parse($end)->format('M j, Y g:i A') : '—' }}</span>
            </div>
        </div>
        @if(!$election->isClosed())
        <button onclick="confirmReset(this)" id="reset-votes-btn"
            class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 py-2 rounded-xl transition-colors shadow inline-flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
            </svg>
            Reset All Votes (this election)
        </button>
        @endif
    @else
        <p class="text-sm text-gray-400 dark:text-white/40">No election created yet. <a href="{{ route('admin.elections.index') }}" class="text-secondary dark:text-gold-bright font-semibold">Create one</a> to get started.</p>
    @endif
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
@if($turnoutByCourse->isNotEmpty())
new Chart(document.getElementById('turnoutByCourseChart'), {
    type: 'bar',
    data: {
        labels: @json($turnoutByCourse->pluck('course')),
        datasets: [
            {
                label: 'Voted',
                data: @json($turnoutByCourse->pluck('voted')),
                backgroundColor: '#001f3f',
                borderRadius: 6,
            },
            {
                label: 'Not Yet Voted',
                data: @json($turnoutByCourse->map(fn($c) => $c['total'] - $c['voted'])),
                backgroundColor: '#DAA520',
                borderRadius: 6,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 500,
            easing: 'easeOutQuart',
        },
        scales: {
            x: { stacked: true, grid: { display: false } },
            y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
        },
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    afterBody: (items) => {
                        const i = items[0].dataIndex;
                        const pct = @json($turnoutByCourse->pluck('percent'))[i];
                        return `Turnout: ${pct}%`;
                    }
                }
            }
        }
    }
});
@endif

async function confirmReset(btn) {
    if (!(await confirmDialog('Are you sure you want to RESET ALL VOTES? This cannot be undone.', { title: 'Reset All Votes', danger: true }))) return;
    const res = await withButtonLoading(btn, 'Resetting…', () => apiFetch('{{ route("admin.results.reset") }}', { method: 'POST' }));
    await alertDialog(res.success ? res.message : 'Error: ' + res.message, { danger: !res.success });
    if (res.success) location.reload();
}

// Count the stat card numbers up from 0 instead of having them appear
// instantly. Respects prefers-reduced-motion by snapping straight to the
// final value.
(function animateStatCards() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('.stat-value').forEach(el => {
        const target = parseInt(el.dataset.target, 10) || 0;
        if (reduceMotion) {
            el.textContent = target;
            return;
        }
        const duration = 400;
        let start = null;
        function step(ts) {
            if (!start) start = ts;
            const progress = Math.min((ts - start) / duration, 1);
            el.textContent = Math.round(progress * target);
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });
})();
</script>
@endpush
@endsection

@extends('layouts.app')
@section('title', 'JRMSU SSG E-Voting — Voter Portal')

@section('body')
<div x-data="darkMode" class="min-h-screen bg-gray-50 dark:bg-ink transition-colors duration-200">

    {{-- Header --}}
    <header class="seal-weave bg-primary dark:bg-ink dark:border-b dark:border-white/10 text-white shadow-lg sticky top-0 z-20">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="{{ $brand['logo_url'] ?? asset('images/SSG.jpg') }}" alt="{{ $brand['school_short_name'] ?? 'SSG' }}" class="h-10 w-10 rounded-full object-cover border-2 border-secondary">
                <div>
                    <div class="font-serif font-semibold text-base leading-none tracking-tight">{{ $brand['school_name'] ?? 'JRMSU Siocon SSG' }}</div>
                    <div class="text-[10px] text-secondary uppercase tracking-widest mt-1">{{ $brand['tagline'] ?? 'Official Ballot · E-Voting System' }}</div>
                </div>
            </div>
            <div class="flex items-center space-x-3 sm:space-x-4">
                <span class="hidden sm:block text-sm text-white/80">
                    {{ $voter->name }}
                    @if($alreadyVoted)
                        <span class="ml-2 text-xs bg-green-500 text-white px-2 py-0.5 rounded-full font-semibold inline-flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3 w-3">
                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 7.53l-4.75 4.75a.75.75 0 01-1.06 0l-2.25-2.25a.75.75 0 111.06-1.06l1.72 1.72 4.22-4.22a.75.75 0 111.06 1.06z" clip-rule="evenodd" />
                            </svg>
                            Voted
                        </span>
                    @endif
                </span>
                <button @click="toggle()" type="button" title="Toggle dark mode"
                    class="text-xs text-white/70 hover:text-white border border-white/30 w-8 h-8 rounded-lg transition flex items-center justify-center">
                    <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                    <svg x-show="dark" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                </button>
                <form method="POST" action="{{ route('voter.logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-white/70 hover:text-white border border-white/30 px-3 py-1.5 rounded-lg transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8">

        {{-- Flash: already voted --}}
        @if(session('voted') || $alreadyVoted)
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 mb-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-9 w-9">
                    <path fill-rule="evenodd" d="M16.403 12.652a3 3 0 000-5.304 3 3 0 00-3.75-3.751 3 3 0 00-5.305 0 3 3 0 00-3.751 3.75 3 3 0 000 5.305 3 3 0 003.75 3.751 3 3 0 005.305 0 3 3 0 003.751-3.75zm-2.546-4.46a.75.75 0 00-1.214-.883l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
            </div>
            <h2 class="font-serif text-2xl font-semibold text-green-700 dark:text-green-400 mb-2 tracking-tight">Your vote has been recorded</h2>
            <p class="text-green-600 dark:text-green-400/90 text-sm">Thank you, {{ $voter->name }}. Your ballot has been successfully submitted.</p>

            @php($displayReceipt = session('receipt_code') ?? $receiptCode ?? null)
            @if($displayReceipt)
            <div class="foil-edge stamp-in mt-5 inline-block bg-white dark:bg-white/5 border border-parchment-line dark:border-white/10 rounded-xl px-6 py-4 shadow-md">
                <div class="ballot-stub mb-3 w-40 mx-auto"></div>
                <div class="text-[11px] uppercase tracking-widest text-secondary font-semibold mb-1">Your Voter Receipt Code</div>
                <div class="font-mono text-xl font-bold text-primary dark:text-white tracking-wider select-all">{{ $displayReceipt }}</div>
                <p class="text-[11px] text-gray-400 mt-1">Keep this as your proof of participation. It does not reveal who you voted for.</p>
                <a href="{{ route('verify.show') }}?receipt_code={{ urlencode($displayReceipt) }}"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 mt-4 text-xs font-semibold text-secondary hover:text-primary dark:hover:text-white border border-secondary/40 hover:border-secondary rounded-lg px-3 py-1.5 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    Verify My Vote
                </a>
            </div>
            @endif
        </div>

        {{-- Show live results to voter after voting --}}
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-serif font-semibold text-primary dark:text-white text-lg tracking-tight">Live Results</h3>
            <span class="text-xs text-gray-400">Updates every 10 seconds</span>
        </div>
        <div id="voter-results" class="space-y-4">
            <p class="text-gray-400 text-sm text-center py-8">Loading…</p>
        </div>

        @elseif(! $status['open'])
        {{-- Voting closed --}}
        <div class="bg-white dark:bg-white/5 rounded-2xl shadow-xl p-8 text-center border-t-4 border-red-500 mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8">
                    <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
                </svg>
            </div>
            <h2 class="font-serif text-2xl font-semibold text-gray-800 dark:text-white mb-2 tracking-tight">Voting Unavailable</h2>
            <p class="text-gray-600 dark:text-gray-400">{{ $status['message'] }}</p>
        </div>

        @else
        {{-- Election status banner --}}
        <div class="seal-weave foil-edge bg-primary dark:bg-white/5 dark:border dark:border-white/10 rounded-2xl shadow-lg px-6 py-4 mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="relative flex h-3 w-3 shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-400"></span>
                </span>
                <div>
                    <div class="text-white text-sm font-semibold tracking-wide">{{ $status['election']->title ?? 'Election' }} — Voting is Open</div>
                    @if($status['election']?->end_time)
                    <div class="text-white/70 text-xs">Closes {{ $status['election']->end_time->format('F j, Y \a\t g:i A') }}</div>
                    @endif
                </div>
            </div>
            <span class="text-[11px] uppercase tracking-widest bg-secondary text-primary font-bold px-3 py-1 rounded-full">Live</span>
        </div>

        {{-- Voter info card --}}
        <div class="bg-white dark:bg-white/5 border border-gray-100 dark:border-white/10 rounded-2xl shadow-sm px-6 py-4 mb-8 flex items-center gap-4">
            <div class="h-12 w-12 rounded-full bg-primary/10 dark:bg-white/10 text-primary dark:text-white flex items-center justify-center font-bold text-lg shrink-0">
                {{ strtoupper(substr($voter->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <div class="font-semibold text-gray-800 dark:text-white truncate">{{ $voter->name }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate">
                    {{ $voter->student_id ?? '—' }} @if($voter->course) · {{ $voter->course }} @endif
                </div>
            </div>
            <span class="ml-auto shrink-0 text-[11px] uppercase tracking-widest bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300 font-semibold px-3 py-1 rounded-full">
                Not Yet Voted
            </span>
        </div>

        {{-- Ballot form --}}
        <div class="mb-6">
            <h2 class="font-serif text-xl font-semibold text-primary dark:text-white tracking-tight">Official Ballot</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Select exactly one candidate per position. All positions are required.</p>
        </div>

        <form method="POST" action="{{ route('voter.vote') }}" id="ballot-form">
            @csrf
            <div class="space-y-6">
                @foreach($positions as $position)
                <div class="bg-white dark:bg-white/5 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-white/10" data-position="{{ $position->name }}">
                    <div class="bg-gray-50 dark:bg-white/5 px-6 py-4 border-b border-gray-100 dark:border-white/10 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-primary dark:text-white">{{ $position->name }}</h3>
                        <span class="text-xs bg-secondary text-white px-3 py-1 rounded-full shadow-sm font-semibold">
                            Select {{ $position->max_votes }}
                        </span>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($position->candidates as $candidate)
                        <label class="cursor-pointer group flex h-full">
                            @if($position->max_votes > 1)
                            <input type="checkbox"
                                name="votes[{{ $position->id }}][]"
                                value="{{ $candidate->id }}"
                                class="sr-only jrmsu-choice"
                                data-position="{{ $position->id }}"
                                data-max="{{ $position->max_votes }}">
                            @else
                            <input type="radio"
                                name="votes[{{ $position->id }}]"
                                value="{{ $candidate->id }}"
                                class="sr-only jrmsu-choice"
                                data-position="{{ $position->id }}"
                                data-max="{{ $position->max_votes }}"
                                required>
                            @endif
                            <div class="w-full p-4 rounded-xl border-2 border-gray-200 dark:border-white/10 group-hover:border-secondary group-hover:shadow-md
                                        transition-all flex items-center bg-white dark:bg-white/5 shadow-sm ballot-card">
                                @if($candidate->image_url)
                                    <img src="{{ $candidate->image_url }}" alt="{{ $candidate->name }}"
                                         class="h-16 w-16 md:h-20 md:w-20 rounded-full object-cover mr-4 shrink-0 border border-gray-200 dark:border-white/10 shadow-sm">
                                @else
                                    <div class="h-16 w-16 md:h-20 md:w-20 rounded-full bg-gray-200 dark:bg-white/10 text-gray-600 dark:text-gray-300 flex items-center
                                                justify-center font-bold mr-4 shrink-0 group-hover:bg-secondary group-hover:text-white
                                                transition-colors shadow-sm text-2xl">
                                        {{ strtoupper(substr($candidate->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-gray-800 dark:text-white text-base md:text-lg truncate candidate-name">{{ $candidate->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $candidate->party_list ?? 'Independent' }}</div>
                                </div>
                                <div class="check-icon opacity-0 scale-50 transition-all duration-200 text-secondary ml-2 shrink-0">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </label>
                        @empty
                        <p class="text-gray-400 italic col-span-2 text-sm">No candidates for this position.</p>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>

            <div class="ballot-stub mt-10 mb-8"></div>

            <div class="flex justify-end">
                <button type="button" onclick="openBallotConfirmModal()"
                    class="w-full sm:w-auto bg-gradient-to-r from-primary to-secondary text-white text-lg font-bold
                           py-4 px-10 rounded-xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-200">
                    Submit Official Ballot
                </button>
            </div>
        </form>

        {{-- Ballot Confirmation Modal --}}
        <div id="ballot-confirm-modal"
             role="dialog" aria-modal="true" aria-labelledby="ballot-confirm-title" aria-describedby="ballot-confirm-desc"
             class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm px-4">
            <div class="bg-white dark:bg-[#0f1a2e] rounded-2xl shadow-2xl max-w-md w-full p-8 text-center max-h-[90vh] overflow-y-auto">
                <div aria-hidden="true" class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 dark:bg-white/10 text-primary dark:text-secondary mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-9 w-9">
                        <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.75.75 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h2 id="ballot-confirm-title" class="font-serif text-2xl font-semibold text-primary dark:text-white mb-2 tracking-tight">Confirm Your Ballot</h2>
                <p id="ballot-confirm-desc" class="text-gray-600 dark:text-gray-400 text-sm mb-2">
                    You are about to submit your <strong>official ballot</strong>.
                </p>
                <p class="text-red-500 dark:text-red-400 text-sm font-semibold mb-6 flex items-center justify-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    This action is <u>permanent</u> and cannot be changed once submitted.
                </p>
                <div id="ballot-summary" class="text-left bg-gray-50 dark:bg-white/5 rounded-xl p-4 mb-6 text-sm text-gray-700 dark:text-gray-300 space-y-1 max-h-72 overflow-y-auto"></div>
                <div class="flex gap-3">
                    <button onclick="closeBallotConfirmModal()"
                        class="flex-1 border border-gray-300 dark:border-white/20 text-gray-600 dark:text-gray-300 font-semibold py-3 rounded-xl hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        ← Go Back
                    </button>
                    <button onclick="submitBallot()"
                        class="flex-1 bg-gradient-to-r from-primary to-secondary text-white font-bold py-3 rounded-xl hover:shadow-lg transition-all flex items-center justify-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 shrink-0">
                            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 7.53l-4.75 4.75a.75.75 0 01-1.06 0l-2.25-2.25a.75.75 0 111.06-1.06l1.72 1.72 4.22-4.22a.75.75 0 111.06 1.06z" clip-rule="evenodd" />
                        </svg>
                        Submit Ballot
                    </button>
                </div>
            </div>
        </div>
        @endif
    </main>
</div>

@push('scripts')
<style>
    input[type="radio"].jrmsu-choice:checked + .ballot-card,
    input[type="checkbox"].jrmsu-choice:checked + .ballot-card {
        border-color: #DAA520;
        background-color: #fffbeb;
    }
    .dark input[type="radio"].jrmsu-choice:checked + .ballot-card,
    .dark input[type="checkbox"].jrmsu-choice:checked + .ballot-card {
        background-color: rgba(218,165,32,.12);
    }
    input[type="radio"].jrmsu-choice:checked + .ballot-card .check-icon,
    input[type="checkbox"].jrmsu-choice:checked + .ballot-card .check-icon {
        opacity: 1;
        transform: scale(1);
    }
</style>
<script>
// Enforce each position's max-vote cap client-side: once the limit is hit,
// disable the remaining unchecked checkboxes for that position so a voter
// gets instant feedback instead of a post-submit "ballot voided" rejection.
// This is a UX convenience only — the real enforcement is server-side in
// VoterDashboardController::submitVote(), which voids any ballot that
// still exceeds the limit no matter what the client did.
document.querySelectorAll('input[type="checkbox"].jrmsu-choice').forEach(cb => {
    cb.addEventListener('change', () => enforceMaxForPosition(cb.dataset.position));
    enforceMaxForPosition(cb.dataset.position);
});

function enforceMaxForPosition(positionId) {
    const group = document.querySelectorAll(`input[type="checkbox"].jrmsu-choice[data-position="${positionId}"]`);
    if (!group.length) return;

    const max = parseInt(group[0].dataset.max, 10) || 1;
    const checkedCount = Array.from(group).filter(i => i.checked).length;

    group.forEach(input => {
        if (!input.checked) {
            input.disabled = checkedCount >= max;
            input.closest('label')?.classList.toggle('opacity-40', input.disabled);
            input.closest('label')?.classList.toggle('cursor-not-allowed', input.disabled);
        }
    });
}

// Ballot confirmation modal logic
function openBallotConfirmModal() {
    // Build summary of selections
    const form    = document.getElementById('ballot-form');
    const summary = document.getElementById('ballot-summary');
    const inputs  = form.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked');

    if (inputs.length === 0) {
        alertDialog('Please select at least one candidate before submitting.', { danger: true });
        return;
    }

    let html = '<ul class="space-y-1">';
    inputs.forEach(input => {
        const card  = input.nextElementSibling;
        // SECURITY: .textContent here returns the *decoded* text (any
        // Blade-escaped entities in the source markup are unescaped back to
        // raw characters), so it must be re-escaped before going back into
        // innerHTML below — otherwise a candidate name containing e.g. "<"
        // would be parsed as markup a second time.
        const name  = escapeHtml(card?.querySelector('.candidate-name')?.textContent?.trim() || input.value);
        const pos   = escapeHtml(input.closest('.bg-white.rounded-xl')?.dataset?.position || '');
        const checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 text-green-600 inline-block align-[-2px] mr-1"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 7.53l-4.75 4.75a.75.75 0 01-1.06 0l-2.25-2.25a.75.75 0 111.06-1.06l1.72 1.72 4.22-4.22a.75.75 0 111.06 1.06z" clip-rule="evenodd"/></svg>';
        html += `<li>${checkIcon}<span class="font-medium">${name}</span>${pos ? ' <span class="text-gray-400 text-xs">— ' + pos + '</span>' : ''}</li>`;
    });
    html += '</ul>';
    summary.innerHTML = html || '<p class="text-gray-400">No selections detected.</p>';

    const modal = document.getElementById('ballot-confirm-modal');
    // ACCESSIBILITY: remember what had focus (the Submit button) so it can
    // be restored if the voter backs out, and move focus into the dialog —
    // this is the single most consequential confirmation in the app, so it
    // shouldn't be possible to Tab past it into the page behind it.
    modal.dataset.returnFocusId = document.activeElement?.id || '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.querySelector('button')?.focus({ preventScroll: true });
}

function closeBallotConfirmModal() {
    const modal = document.getElementById('ballot-confirm-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    const returnId = modal.dataset.returnFocusId;
    if (returnId) document.getElementById(returnId)?.focus({ preventScroll: true });
}

function submitBallot() {
    document.getElementById('ballot-form').submit();
}

// Close on backdrop click
document.getElementById('ballot-confirm-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeBallotConfirmModal();
});

// Close on Escape — same as "Go Back", never treated as "Submit"
document.getElementById('ballot-confirm-modal')?.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !this.classList.contains('hidden')) closeBallotConfirmModal();
});

// Live results (shown after voting)
@if(session('voted') || $alreadyVoted)
async function loadVoterResults() {
    const res = await fetch('{{ route("voter.results") }}');
    const data = await res.json();
    if (!data.success) return;

    const container = document.getElementById('voter-results');
    if (!data.data?.length) {
        container.innerHTML = '<p class="text-gray-400 text-sm text-center">No votes recorded yet.</p>';
        return;
    }

    const grouped = {};
    data.data.forEach(r => {
        if (!grouped[r.positionId]) grouped[r.positionId] = { positionName: r.positionName, candidates: [] };
        grouped[r.positionId].candidates.push(r);
    });

    container.innerHTML = '';
    Object.values(grouped).forEach(group => {
        const sorted = [...group.candidates].sort((a, b) => b.voteCount - a.voteCount);
        const topVotes = sorted[0]?.voteCount ?? 0;
        const totalVotes = sorted.reduce((s, c) => s + c.voteCount, 0);

        const bars = sorted.map(c => {
            const pct    = totalVotes > 0 ? Math.round(c.voteCount / totalVotes * 100) : 0;
            const isLeader = c.voteCount > 0 && c.voteCount === topVotes;
            // SECURITY: candidateName/partyList are admin-entered and only
            // validated as plain strings server-side (no HTML stripping) —
            // escape before interpolating, same as every other dynamic
            // render in this app already does via .textContent.
            const safeName  = escapeHtml(c.candidateName);
            const safeParty = escapeHtml(c.partyList || 'Ind.');
            const avatar = c.image
                ? `<img src="${escapeHtml(c.image)}" class="h-8 w-8 rounded-full object-cover border border-gray-200 shrink-0">`
                : `<div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center font-bold text-xs text-gray-600 shrink-0">${escapeHtml(c.candidateName.charAt(0))}</div>`;
            const starIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5 text-secondary inline-block align-[-2px] mr-0.5"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd"/></svg>';
            return `
            <div class="mb-3">
                <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center space-x-2 min-w-0">
                        ${avatar}
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">${isLeader ? starIcon : ''}${safeName}</span>
                        <span class="text-xs text-gray-400 truncate hidden sm:inline">${safeParty}</span>
                    </div>
                    <div class="text-right ml-3 shrink-0">
                        <span class="font-bold text-primary text-sm">${c.voteCount}</span>
                        <span class="text-xs text-gray-400 ml-1">(${pct}%)</span>
                    </div>
                </div>
                <div class="w-full bg-gray-100 dark:bg-white/10 rounded-full h-2.5">
                    <div class="bg-secondary h-2.5 rounded-full transition-all duration-700" style="width:${pct}%"></div>
                </div>
            </div>`;
        }).join('');

        container.innerHTML += `
        <div class="bg-white dark:bg-white/5 rounded-xl shadow-sm border border-gray-100 dark:border-white/10 p-5">
            <h4 class="font-bold text-primary dark:text-white mb-4 text-sm uppercase tracking-wide">${escapeHtml(group.positionName)}</h4>
            ${bars}
        </div>`;
    });
}
loadVoterResults();
setInterval(loadVoterResults, 10000);
@endif
</script>
@endpush
@endsection

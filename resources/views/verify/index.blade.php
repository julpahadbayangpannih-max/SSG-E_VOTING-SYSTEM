@extends('layouts.app')
@section('title', 'Verify My Vote — JRMSU SSG E-Voting')

@section('body')
<div x-data="darkMode" class="min-h-screen bg-parchment dark:bg-ink transition-colors duration-200">

    {{-- Header --}}
    <header class="seal-weave bg-primary dark:bg-ink border-b border-transparent dark:border-white/10 text-white shadow-lg sticky top-0 z-20">
        <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center space-x-3">
                <img src="{{ asset('images/SSG.jpg') }}" alt="SSG" class="h-10 w-10 rounded-full object-cover border-2 border-secondary">
                <div>
                    <div class="font-serif font-semibold text-base leading-none tracking-tight">JRMSU Siocon SSG</div>
                    <div class="text-[10px] text-secondary uppercase tracking-widest mt-1">Verify My Vote</div>
                </div>
            </a>
            <button @click="toggle()" type="button"
                class="text-xs text-white/70 hover:text-white border border-white/30 px-3 py-1.5 rounded-lg transition flex items-center gap-1.5">
                <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
            </svg>
        <svg x-show="dark" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
            </svg>
                <span class="hidden sm:inline" x-text="dark ? 'Light' : 'Dark'"></span>
            </button>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-10">

        {{-- Intro --}}
        <div class="text-center mb-8">
            <div class="seal-emboss inline-flex items-center justify-center w-16 h-16 rounded-full bg-white dark:bg-white/5 border border-parchment-line dark:border-white/10 mb-4 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-7 w-7 text-secondary">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            <h1 class="font-serif text-3xl font-bold text-primary dark:text-white tracking-tight mb-2">Verify My Vote</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                Enter the receipt code you were given after voting to confirm your ballot was
                recorded and included in the official count — without revealing who you voted for.
            </p>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('verify.check') }}"
            class="foil-edge bg-white dark:bg-white/5 border border-parchment-line dark:border-white/10 rounded-2xl shadow-md p-6 mb-8">
            @csrf
            <label for="receipt_code" class="block text-xs uppercase tracking-widest font-semibold text-secondary mb-2">
                Receipt Code
            </label>
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="receipt_code" id="receipt_code" required autofocus
                    placeholder="JRMSU-XXXXXXXX"
                    value="{{ old('receipt_code', $submittedCode) }}"
                    class="flex-1 font-mono tracking-wider text-lg border-2 border-gray-200 dark:border-white/10 dark:bg-transparent dark:text-white rounded-xl px-4 py-3 focus:outline-none focus:border-secondary transition-colors">
                <button type="submit"
                    class="bg-gradient-to-r from-primary to-secondary text-white font-bold px-6 py-3 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">
                    Verify
                </button>
            </div>
            @error('receipt_code')
                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
            @enderror
        </form>

        {{-- Result --}}
        @if($result)
            @switch($result['status'])

                @case('certified')
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-9 w-9">
                                <path fill-rule="evenodd" d="M16.403 12.652a3 3 0 000-5.304 3 3 0 00-3.75-3.751 3 3 0 00-5.305 0 3 3 0 00-3.751 3.75 3 3 0 000 5.305 3 3 0 003.75 3.751 3 3 0 005.305 0 3 3 0 003.751-3.75zm-2.546-4.46a.75.75 0 00-1.214-.883l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h2 class="font-serif text-xl font-semibold text-green-700 dark:text-green-400 mb-1">Ballot verified &amp; certified</h2>
                        <p class="text-green-600 dark:text-green-400/90 text-sm mb-4">
                            Your ballot for <strong>{{ $result['election_title'] }}</strong> was recorded and is
                            provably part of the final, certified tally. Nothing has changed since results were certified.
                        </p>
                        @include('verify.partials.details')
                    </div>
                    @break

                @case('provisional')
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-8 w-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="font-serif text-xl font-semibold text-amber-700 dark:text-amber-400 mb-1">Ballot found — provisional</h2>
                        <p class="text-amber-700 dark:text-amber-400/90 text-sm mb-4">
                            Your ballot for <strong>{{ $result['election_title'] }}</strong> is recorded and currently
                            included in the running tally. <strong>{{ $result['election_title'] }}</strong> is still
                            open, so final certification happens once the election closes — check back after that for
                            a fully certified result.
                        </p>
                        @include('verify.partials.details')
                    </div>
                    @break

                @case('legacy')
                    <div class="bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-2xl p-6 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-300 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-8 w-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="font-serif text-xl font-semibold text-gray-700 dark:text-gray-200 mb-1">Ballot found</h2>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Your ballot for <strong>{{ $result['election_title'] }}</strong> was recorded on
                            {{ $result['voted_at']?->format('F j, Y \a\t g:i A') }}. This ballot predates
                            cryptographic verification, so an inclusion proof isn't available — but the election
                            committee can confirm it from the audit log if needed.
                        </p>
                    </div>
                    @break

                @case('integrity_alert')
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-800 rounded-2xl p-6 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-9 w-9">
                                <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h2 class="font-serif text-xl font-semibold text-red-700 dark:text-red-400 mb-1">Integrity check failed</h2>
                        <p class="text-red-600 dark:text-red-400/90 text-sm">
                            The published, certified results for <strong>{{ $result['election_title'] }}</strong> no
                            longer match what's currently recorded. This does not necessarily mean your ballot was
                            affected, but it means something changed after certification. Please report this to the
                            election committee immediately along with your receipt code.
                        </p>
                    </div>
                    @break

                @case('not_found')
                    <div class="bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-2xl p-6 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-300 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-8 w-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <h2 class="font-serif text-xl font-semibold text-gray-700 dark:text-gray-200 mb-1">No ballot found</h2>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            No ballot matches that receipt code. Double-check it was copied exactly, including the
                            <code class="font-mono text-xs">JRMSU-</code> prefix.
                        </p>
                    </div>
                    @break

                @default
                    <div class="bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-2xl p-6 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-300 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-8 w-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <h2 class="font-serif text-xl font-semibold text-gray-700 dark:text-gray-200 mb-1">Couldn't verify right now</h2>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Please try again in a moment, or contact the election committee.</p>
                    </div>
            @endswitch
        @endif

        <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-8">
            <a href="{{ route('home') }}" class="hover:underline">← Back to homepage</a>
        </p>
    </main>
</div>
@endsection

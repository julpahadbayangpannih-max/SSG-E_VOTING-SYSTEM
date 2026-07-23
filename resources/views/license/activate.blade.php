@extends('layouts.app')
@section('title', 'Activate License – ' . ($brand['school_short_name'] ?? 'JRMSU') . ' E-Voting')

@section('body')
<div x-data="darkMode" class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-ink px-4 transition-colors duration-200">
    <button @click="toggle()" type="button" title="Toggle dark mode"
        class="fixed top-4 right-4 text-xs text-gray-500 dark:text-white/70 hover:text-gray-800 dark:hover:text-white border border-gray-300 dark:border-white/30 w-9 h-9 rounded-lg transition flex items-center justify-center bg-white/80 dark:bg-white/5 backdrop-blur">
        <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
        </svg>
        <svg x-show="dark" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
        </svg>
    </button>

    <div class="foil-edge w-full max-w-md bg-white dark:bg-white/5 dark:border dark:border-white/10 p-8 rounded-xl shadow-2xl border-t-4 border-secondary">
        <div class="text-center mb-8">
            <div class="seal-emboss inline-block">
            @if(!empty($brand['logo_url']))
                <img src="{{ $brand['logo_url'] }}" alt="Logo" class="mx-auto w-20 h-20 rounded-full object-cover border-2 border-secondary shadow-md mb-4">
            @else
                <div class="mx-auto w-20 h-20 rounded-full bg-primary/10 dark:bg-white/10 flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-9 w-9 text-primary dark:text-secondary">
                        <path fill-rule="evenodd" d="M15.75 1.5a6.75 6.75 0 00-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 00-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 00.75-.75v-1.5h1.5A.75.75 0 009 19.5V18h1.5a.75.75 0 00.53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1015.75 1.5zm0 3a.75.75 0 000 1.5A2.25 2.25 0 0118 8.25a.75.75 0 001.5 0 3.75 3.75 0 00-3.75-3.75z" clip-rule="evenodd" />
                    </svg>
                </div>
            @endif
            </div>
            <h1 class="font-serif text-2xl font-semibold text-primary dark:text-white tracking-tight">Activate This Installation</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ $brand['school_name'] ?? 'This system' }} needs a valid license key to run.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-sm rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('license.activate.post') }}">
            @csrf
            <div class="mb-6">
                <label for="license_key" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">License Key</label>
                <input type="text" id="license_key" name="license_key" required value="{{ old('license_key') }}"
                    class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm font-mono rounded-lg focus:ring-secondary focus:border-secondary block w-full p-2.5 outline-none transition-all"
                    placeholder="ABCDE-FGHIJ-KLMNO-PQRST" autocomplete="off">
            </div>
            <button type="submit"
                class="w-full text-white bg-primary hover:bg-secondary focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors duration-300">
                Activate
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-gray-400 dark:text-gray-500 space-y-1">
            <p>Don't have a key? Run <code class="bg-gray-100 dark:bg-white/10 px-1 rounded">php artisan license:generate</code> on the server hosting this install.</p>
            <p>&copy; {{ date('Y') }} {{ $brand['school_name'] ?? '' }}. All rights reserved.</p>
        </div>
    </div>
</div>
@endsection

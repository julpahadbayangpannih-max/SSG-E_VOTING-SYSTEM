@extends('layouts.app')
@section('title', 'Verification Required – JRMSU SSG E-Voting')

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
            <div class="mx-auto w-16 h-16 rounded-full bg-primary/10 dark:bg-white/10 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-primary dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-primary dark:text-white">Two-Factor Verification</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Enter the 6-digit code from your authenticator app.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-sm rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $errors->first() }}
            </div>
        @endif

        @error('_throttle')
            <div class="text-sm text-red-600 dark:text-red-400 mb-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4 inline-block shrink-0 align-[-3px] mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>Too many attempts. Please wait 1 minute before trying again.
            </div>
        @enderror

        <form method="POST" action="{{ route('admin.2fa.challenge.post') }}">
            @csrf
            <div class="mb-6">
                <label for="code" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Authentication code</label>
                <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                    maxlength="10" autofocus required
                    class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-center text-2xl tracking-[0.4em] rounded-lg focus:ring-secondary focus:border-secondary block w-full p-3 outline-none transition-all"
                    placeholder="000000">
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Lost your device? You can enter one of your recovery codes here instead.</p>
            </div>

            <button type="submit"
                class="w-full text-white bg-primary hover:bg-secondary focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors duration-300">
                Verify
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('admin.login') }}" class="text-xs text-gray-400 dark:text-gray-500 hover:text-primary dark:hover:text-white">← Back to login</a>
        </div>
    </div>
</div>
@endsection

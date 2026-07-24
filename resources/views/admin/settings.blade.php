@extends('layouts.admin')
@section('page-title', 'Settings')
@section('page-subtitle', 'White-label branding and license status.')
@php $activeView = 'settings'; @endphp

@section('content')

@if(session('status'))
<div class="mb-6 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/40 text-green-700 dark:text-green-300 text-sm rounded-lg">{{ session('status') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- White-labeling --}}
    <div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm border border-gray-100 dark:border-white/10 p-6">
        <h2 class="font-bold text-primary dark:text-parchment mb-1">School Branding</h2>
        <p class="text-xs text-gray-400 dark:text-white/40 mb-5">Shown across the admin panel, voter portal, and results PDF export.</p>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">School Name (full)</label>
                <input type="text" name="school_name" value="{{ old('school_name', $brand['school_name']) }}"
                    class="bg-white dark:bg-white/10 text-gray-800 dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary"
                    placeholder="JRMSU Siocon Campus">
                @error('school_name')<p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Short Name (badge/logo label)</label>
                <input type="text" name="school_short_name" value="{{ old('school_short_name', $brand['school_short_name']) }}"
                    class="bg-white dark:bg-white/10 text-gray-800 dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary"
                    placeholder="JRMSU">
                @error('school_short_name')<p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Tagline</label>
                <input type="text" name="school_tagline" value="{{ old('school_tagline', $brand['tagline']) }}"
                    class="bg-white dark:bg-white/10 text-gray-800 dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary"
                    placeholder="SSG Election · E-Voting System">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Logo</label>
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-lg bg-slate-50 dark:bg-white/10 border border-gray-200 dark:border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                        @if(!empty($brand['logo_url']))
                            <img src="{{ $brand['logo_url'] }}" class="h-full w-full object-cover">
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-7 w-7 text-gray-400 dark:text-white/40">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 4.5-9 4.5-9-4.5L12 3zM3.75 9.75v6.75c0 .621 2.786 3 8.25 3s8.25-2.379 8.25-3V9.75" />
                            </svg>
                        @endif
                    </div>
                    <input type="file" name="logo" accept="image/*" class="text-sm">
                </div>
                @error('logo')<p class="text-red-600 dark:text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-[11px] text-gray-400 dark:text-white/40 mt-1">JPG, PNG, WEBP or SVG, up to 2MB. Leave empty to keep the current logo.</p>
            </div>
            <button type="submit" class="bg-primary hover:bg-secondary text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors shadow">
                Save Branding
            </button>
        </form>
    </div>

    {{-- License --}}
    <div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm border border-gray-100 dark:border-white/10 p-6">
        <h2 class="font-bold text-primary dark:text-parchment mb-1">License</h2>
        <p class="text-xs text-gray-400 dark:text-white/40 mb-5">This installation's activation status.</p>

        <div class="flex items-center gap-3 mb-5">
            @if($licenseActivated)
                <span class="inline-flex items-center gap-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs font-bold px-3 py-1.5 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 7.53l-4.75 4.75a.75.75 0 01-1.06 0l-2.25-2.25a.75.75 0 111.06-1.06l1.72 1.72 4.22-4.22a.75.75 0 111.06 1.06z" clip-rule="evenodd"/></svg>
                    Activated
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs font-bold px-3 py-1.5 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    Not Activated
                </span>
            @endif
        </div>

        @if($licenseKey)
        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Active License Key</label>
            <div class="font-mono text-sm text-gray-800 dark:text-white/90 bg-slate-50 dark:bg-white/10 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2">{{ $licenseKey }}</div>
        </div>
        @endif

        <div class="bg-slate-50 dark:bg-white/10 border border-gray-200 dark:border-white/10 rounded-xl p-4 text-xs text-gray-500 dark:text-white/50 leading-relaxed">
            <p class="font-semibold text-gray-600 dark:text-white/70 mb-1">To generate or re-generate a key for this server:</p>
            <code class="block text-gray-800 dark:text-white/90 bg-white dark:bg-[#0f2a4a] border border-gray-200 dark:border-white/10 rounded px-2 py-1.5 mt-1">php artisan license:generate</code>
            <p class="mt-2">The key is derived from this install's <code>APP_KEY</code>, so deploying the same codebase to a different server requires generating a new key there.</p>
        </div>
    </div>

</div>
@endsection

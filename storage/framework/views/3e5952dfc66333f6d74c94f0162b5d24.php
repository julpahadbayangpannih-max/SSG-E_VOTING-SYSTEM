<?php $__env->startSection('body'); ?>
<div id="top-progress-bar"></div>
<div class="flex h-screen overflow-hidden">
    <?php if (isset($component)) { $__componentOriginal6fc2d165f80d597f34aa0f8014c366d2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-sidebar','data' => ['active' => $activeView ?? '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeView ?? '')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2)): ?>
<?php $attributes = $__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2; ?>
<?php unset($__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6fc2d165f80d597f34aa0f8014c366d2)): ?>
<?php $component = $__componentOriginal6fc2d165f80d597f34aa0f8014c366d2; ?>
<?php unset($__componentOriginal6fc2d165f80d597f34aa0f8014c366d2); ?>
<?php endif; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden seal-weave">
        
        <header class="bg-white dark:bg-[#0f2a4a] border-b border-parchment-line dark:border-white/10 px-4 md:px-8 py-4 flex items-center justify-between sticky top-0 z-20 transition-colors">
            <div class="flex items-center space-x-3">
                <button id="open-sidebar" class="md:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10">
                    <svg class="h-6 w-6 text-primary dark:text-gold-bright" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
                <div>
                    <h1 class="font-serif text-xl font-semibold text-primary dark:text-parchment tracking-tight"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h1>
                    <p class="text-xs text-gray-400 dark:text-white/50 hidden sm:block"><?php echo $__env->yieldContent('page-subtitle', ''); ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="hidden sm:block text-sm text-gray-600 dark:text-white/70">
                    Welcome, <strong class="dark:text-parchment"><?php echo e(Auth::guard('admin')->user()->name); ?></strong>
                </span>
                <div class="h-9 w-9 bg-primary dark:bg-secondary rounded-full flex items-center justify-center text-white dark:text-primary text-sm font-bold">
                    <?php echo e(strtoupper(substr(Auth::guard('admin')->user()->name, 0, 1))); ?>

                </div>
            </div>
        </header>

        
        <?php if(session('success')): ?>
        <div class="mx-4 md:mx-8 mt-4 p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm rounded-lg" id="flash-msg">
            <?php echo e(session('success')); ?>

        </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
        <div class="mx-4 md:mx-8 mt-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 text-sm rounded-lg" id="flash-msg">
            <?php echo e(session('error')); ?>

        </div>
        <?php endif; ?>

        <main class="flex-1 p-4 md:p-8 overflow-auto animate-settle">
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    // Sidebar mobile toggle
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    document.getElementById('open-sidebar')?.addEventListener('click', () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    });
    document.getElementById('close-sidebar')?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
    // Auto-hide flash
    setTimeout(() => document.getElementById('flash-msg')?.remove(), 4000);

    // Global CSRF helper for fetch()
    window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Top progress bar: shows for the duration of any apiFetch call. Counts
    // concurrent requests so overlapping calls don't hide it too early.
    let inFlight = 0;
    const progressBar = document.getElementById('top-progress-bar');
    function beginProgress() {
        inFlight++;
        progressBar.classList.add('active');
    }
    function endProgress() {
        inFlight = Math.max(0, inFlight - 1);
        if (inFlight === 0) progressBar.classList.remove('active');
    }

    window.apiFetch = async (url, options = {}) => {
        const defaults = {
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
        };
        const merged = { ...defaults, ...options, headers: { ...defaults.headers, ...options.headers } };
        beginProgress();
        try {
            const res = await fetch(url, merged);
            return await res.json();
        } finally {
            endProgress();
        }
    };

    // Button-loading helper: disables the button, swaps its label for a
    // spinner + optional text, and restores it afterward — whether the
    // call succeeds, fails, or the page navigates away first.
    // Usage: withButtonLoading(event.currentTarget, 'Saving…', () => saveThing());
    window.withButtonLoading = async (btn, loadingLabel, fn) => {
        if (!btn || btn.classList.contains('is-loading')) return fn();
        const originalHtml = btn.innerHTML;
        btn.classList.add('is-loading');
        btn.innerHTML = `<span class="spinner"></span>${loadingLabel ? ' ' + loadingLabel : ''}`;
        try {
            return await fn();
        } finally {
            btn.classList.remove('is-loading');
            btn.innerHTML = originalHtml;
        }
    };
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\JULFAHAD_SSG_EVOTING\resources\views/layouts/admin.blade.php ENDPATH**/ ?>
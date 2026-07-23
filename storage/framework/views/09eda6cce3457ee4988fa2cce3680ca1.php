<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'JRMSU SSG E-Voting'); ?></title>
    <script>
        // Anti-flash: set the dark/light class before the page paints, using
        // the same storage key and OS-preference fallback as the Alpine
        // `darkMode` component (resources/js/app.js). Without this, every
        // page briefly flashes light mode while Vite's deferred JS loads,
        // then snaps to dark for anyone who has it enabled — this runs
        // synchronously in <head>, before first paint, so there's nothing
        // to flash.
        (function () {
            var stored = localStorage.getItem('jrmsu-theme');
            var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (dark) document.documentElement.classList.add('dark');
        })();

        // SECURITY: shared HTML-escaping helper. Every page that builds
        // markup client-side from server-supplied strings (candidate names,
        // position names, party lists — all admin-entered, validated only
        // as plain strings server-side, never HTML-stripped) MUST run those
        // strings through this before interpolating them into innerHTML.
        // .textContent avoids the problem entirely and is used everywhere
        // else in this app, but the live-results renderers (voter dashboard
        // + admin results) need to build richer markup (avatars, bars,
        // badges) around the text, so here we escape just the untrusted
        // piece and leave the surrounding markup hand-written. Defined once
        // on `window` so every page extending this layout can call it.
        window.escapeHtml = function (value) {
            return String(value ?? '').replace(/[&<>"']/g, function (ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #001f3f; border-radius: 10px; }
        <?php echo $__env->yieldContent('extra-styles'); ?>
    </style>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="text-gray-800">
    <?php echo $__env->yieldContent('body'); ?>

    
    <div id="global-dialog" role="dialog" aria-modal="true" aria-labelledby="global-dialog-title" aria-describedby="global-dialog-message" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div class="foil-edge bg-white dark:bg-[#0f1a2e] rounded-2xl shadow-2xl max-w-sm w-full p-7 text-center">
            <div id="global-dialog-icon" aria-hidden="true" class="inline-flex items-center justify-center w-14 h-14 rounded-full mb-4"></div>
            <h2 id="global-dialog-title" class="font-serif text-xl font-semibold text-primary dark:text-white mb-2 tracking-tight"></h2>
            <p id="global-dialog-message" class="text-gray-600 dark:text-gray-400 text-sm whitespace-pre-line mb-6"></p>
            <div id="global-dialog-actions" class="flex gap-3"></div>
        </div>
    </div>
    <script>
    (function () {
        const dialog    = document.getElementById('global-dialog');
        const iconEl    = document.getElementById('global-dialog-icon');
        const titleEl   = document.getElementById('global-dialog-title');
        const msgEl     = document.getElementById('global-dialog-message');
        const actionsEl = document.getElementById('global-dialog-actions');

        const WARNING_ICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-7 w-7 text-red-500 dark:text-red-400"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>';
        const INFO_ICON    = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7 text-primary dark:text-secondary"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 000-5.304 3 3 0 00-3.75-3.751 3 3 0 00-5.305 0 3 3 0 00-3.751 3.75 3 3 0 000 5.305 3 3 0 003.75 3.751 3 3 0 005.305 0 3 3 0 003.751-3.75zm-2.546-4.46a.75.75 0 00-1.214-.883l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>';

        // ACCESSIBILITY: remember what had focus before the dialog opened so
        // it can be restored on close (otherwise focus silently drops to
        // <body>, disorienting keyboard/screen-reader users), and move focus
        // into the dialog itself so Tab doesn't leave it while open.
        let previouslyFocused = null;

        function openDialog() {
            previouslyFocused = document.activeElement;
            dialog.classList.remove('hidden');
            dialog.classList.add('flex');
            const firstButton = actionsEl.querySelector('button');
            (firstButton || dialog).focus({ preventScroll: true });
        }
        function closeDialog() {
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
            if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
                previouslyFocused.focus({ preventScroll: true });
            }
        }
        // Escape closes like Cancel (confirmDialog) or OK (alertDialog) —
        // whichever button is currently rendered gets clicked, since each
        // dialog wires its own cleanup/resolve logic to its buttons.
        dialog.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !dialog.classList.contains('hidden')) {
                const cancelOrOk = actionsEl.querySelector('button');
                cancelOrOk?.click();
            }
        });

        function button(label, extraClass) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = label;
            btn.className = 'flex-1 font-semibold py-2.5 rounded-xl transition-all ' + extraClass;
            return btn;
        }

        // window.confirmDialog(message, { title, danger }) -> Promise<boolean>
        window.confirmDialog = function (message, opts = {}) {
            const { title = opts.danger ? 'Please Confirm' : 'Confirm Action', danger = false } = opts;
            return new Promise((resolve) => {
                titleEl.textContent = title;
                msgEl.textContent = message;
                iconEl.className = 'inline-flex items-center justify-center w-14 h-14 rounded-full mb-4 ' + (danger ? 'bg-red-100 dark:bg-red-900/30' : 'bg-primary/10 dark:bg-white/10');
                iconEl.innerHTML = danger ? WARNING_ICON : INFO_ICON;
                actionsEl.innerHTML = '';

                const cancelBtn = button('Cancel', 'border border-gray-300 dark:border-white/20 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5');
                const okBtn = button(danger ? 'Yes, Proceed' : 'Confirm', danger
                    ? 'bg-red-600 hover:bg-red-700 text-white shadow'
                    : 'bg-gradient-to-r from-primary to-secondary text-white hover:shadow-lg');

                function cleanup(result) {
                    closeDialog();
                    cancelBtn.removeEventListener('click', onCancel);
                    okBtn.removeEventListener('click', onOk);
                    dialog.removeEventListener('click', onBackdrop);
                    resolve(result);
                }
                function onCancel() { cleanup(false); }
                function onOk() { cleanup(true); }
                function onBackdrop(e) { if (e.target === dialog) cleanup(false); }

                cancelBtn.addEventListener('click', onCancel);
                okBtn.addEventListener('click', onOk);
                dialog.addEventListener('click', onBackdrop);

                actionsEl.appendChild(cancelBtn);
                actionsEl.appendChild(okBtn);
                openDialog();
            });
        };

        // window.alertDialog(message, { title, danger }) -> Promise<void>
        window.alertDialog = function (message, opts = {}) {
            const { title = opts.danger ? 'Error' : 'Notice', danger = false } = opts;
            return new Promise((resolve) => {
                titleEl.textContent = title;
                msgEl.textContent = message;
                iconEl.className = 'inline-flex items-center justify-center w-14 h-14 rounded-full mb-4 ' + (danger ? 'bg-red-100 dark:bg-red-900/30' : 'bg-green-100 dark:bg-green-900/30');
                iconEl.innerHTML = danger ? WARNING_ICON : INFO_ICON;
                actionsEl.innerHTML = '';

                const okBtn = button('OK', 'bg-gradient-to-r from-primary to-secondary text-white hover:shadow-lg');

                function cleanup() {
                    closeDialog();
                    okBtn.removeEventListener('click', onOk);
                    dialog.removeEventListener('click', onBackdrop);
                    resolve();
                }
                function onOk() { cleanup(); }
                function onBackdrop(e) { if (e.target === dialog) cleanup(); }

                okBtn.addEventListener('click', onOk);
                dialog.addEventListener('click', onBackdrop);

                actionsEl.appendChild(okBtn);
                openDialog();
            });
        };
    })();
    </script>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\JULFAHAD_SSG_EVOTING\resources\views/layouts/app.blade.php ENDPATH**/ ?>
<?php $__env->startSection('page-title', 'Security'); ?>
<?php $__env->startSection('page-subtitle', 'Two-factor authentication for your admin account.'); ?>
<?php $activeView = 'security'; ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl">

    <?php if(session('success')): ?>
        <div class="mb-4 text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/40 rounded-xl px-4 py-3">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('2fa_recovery_codes_once')): ?>
        <div class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-2xl p-5">
            <h3 class="font-bold text-primary dark:text-parchment mb-1">Save your recovery codes</h3>
            <p class="text-sm text-gray-600 dark:text-white/70 mb-3">
                Each code works once and lets you sign in if you lose access to your authenticator app.
                They won't be shown again — store them somewhere safe.
            </p>
            <div class="grid grid-cols-2 gap-2 font-mono text-sm bg-white dark:bg-[#0f2a4a] rounded-xl p-4 border border-yellow-200 dark:border-yellow-800/40">
                <?php $__currentLoopData = session('2fa_recovery_codes_once'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($rc); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if($enabled): ?>
        
        <div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm border border-gray-100 dark:border-white/10 p-6">
            <div class="flex items-center gap-3 mb-4">
                <span class="h-3 w-3 rounded-full bg-green-500 dark:bg-green-600"></span>
                <h3 class="font-bold text-primary dark:text-parchment text-lg">Two-factor authentication is ON</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-white/50 mb-6">
                You'll be asked for a code from your authenticator app every time you sign in.
            </p>

            <form id="regen-codes-form" method="POST" action="<?php echo e(route('admin.2fa.recovery.regenerate')); ?>" class="mb-6 border-t border-gray-100 dark:border-white/10 pt-5">
                <?php echo csrf_field(); ?>
                <label class="block text-sm font-medium text-gray-700 dark:text-white/80 mb-1">Generate new recovery codes</label>
                <p class="text-xs text-gray-400 dark:text-white/40 mb-2">Confirm your password to invalidate the old codes and get a fresh set.</p>
                <div class="flex gap-2">
                    <input type="password" name="password" required placeholder="Current password"
                        class="flex-1 border border-gray-300 dark:border-white/20 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-secondary">
                    <button type="submit" class="bg-gray-100 dark:bg-white/10 hover:bg-gray-200 hover:dark:bg-white/10 text-gray-700 dark:text-white/80 text-sm font-semibold px-4 py-2 rounded-xl transition-colors">
                        Regenerate
                    </button>
                </div>
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 dark:text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </form>

            <form id="disable-2fa-form" method="POST" action="<?php echo e(route('admin.2fa.disable')); ?>" class="border-t border-gray-100 dark:border-white/10 pt-5">
                <?php echo csrf_field(); ?>
                <label class="block text-sm font-medium text-gray-700 dark:text-white/80 mb-1">Turn off two-factor authentication</label>
                <div class="flex gap-2 mt-2">
                    <input type="password" name="password" required placeholder="Current password"
                        class="flex-1 border border-gray-300 dark:border-white/20 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:dark:ring-red-500">
                    <button type="submit" class="bg-red-50 dark:bg-red-900/20 hover:bg-red-100 hover:dark:bg-red-900/30 text-red-600 dark:text-red-400 text-sm font-semibold px-4 py-2 rounded-xl transition-colors">
                        Disable
                    </button>
                </div>
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 dark:text-red-400 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </form>
        </div>
    <?php else: ?>
        
        <div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm border border-gray-100 dark:border-white/10 p-6">
            <div class="flex items-center gap-3 mb-4">
                <span class="h-3 w-3 rounded-full bg-gray-300 dark:bg-white/20"></span>
                <h3 class="font-bold text-primary dark:text-parchment text-lg">Two-factor authentication is OFF</h3>
            </div>

            <ol class="text-sm text-gray-600 dark:text-white/70 space-y-4 mb-6">
                <li>
                    <strong class="text-gray-800 dark:text-white/90">1. Scan this QR code</strong> with Google Authenticator, Authy, or any TOTP app.
                    <div class="mt-3 flex justify-center bg-gray-50 dark:bg-white/5 rounded-xl p-4 border border-gray-100 dark:border-white/10">
                        <canvas id="qr-canvas" width="220" height="220"></canvas>
                    </div>
                </li>
                <li>
                    <strong class="text-gray-800 dark:text-white/90">2. Or enter this key manually:</strong>
                    <div class="mt-2 font-mono text-sm bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-4 py-2 tracking-wider break-all">
                        <?php echo e($secret); ?>

                    </div>
                </li>
                <li>
                    <strong class="text-gray-800 dark:text-white/90">3. Enter the 6-digit code</strong> your app shows to confirm setup.
                </li>
            </ol>

            <?php if($errors->any()): ?>
                <div class="mb-4 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 rounded-xl px-4 py-3">
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('admin.2fa.enable')); ?>">
                <?php echo csrf_field(); ?>
                <div class="flex gap-2">
                    <input type="text" name="code" inputmode="numeric" maxlength="6" required autofocus
                        placeholder="000000"
                        class="flex-1 border border-gray-300 dark:border-white/20 rounded-xl px-4 py-2 text-sm text-center tracking-[0.3em] font-mono focus:outline-none focus:ring-2 focus:ring-secondary">
                    <button type="submit" class="bg-primary hover:bg-secondary text-white text-sm font-semibold px-6 py-2 rounded-xl transition-colors">
                        Enable
                    </button>
                </div>
            </form>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
        <script>
            QRCode.toCanvas(
                document.getElementById('qr-canvas'),
                <?php echo json_encode($qrUri, 15, 512) ?>,
                { width: 220, margin: 1 },
                function (error) { if (error) console.error(error); }
            );
        </script>
    <?php endif; ?>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
// These two forms used to gate submission with a native confirm() via
// onsubmit — but that's synchronous and our styled dialog isn't, so instead
// we intercept submit, await the styled confirmation, then submit for real.
function guardFormWithConfirm(formId, message, opts) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', async function (e) {
        if (form.dataset.confirmed === 'true') return; // already confirmed, let it through
        e.preventDefault();
        const ok = await confirmDialog(message, opts);
        if (ok) {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        }
    });
}
guardFormWithConfirm('regen-codes-form', 'This will invalidate all existing recovery codes. Continue?', { danger: true });
guardFormWithConfirm('disable-2fa-form', 'Turn off two-factor authentication? Your account will only be protected by a password.', { danger: true });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\JULFAHAD_SSG_EVOTING\resources\views/admin/two-factor.blade.php ENDPATH**/ ?>
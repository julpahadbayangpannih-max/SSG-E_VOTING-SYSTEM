<?php $__env->startSection('page-title', 'Audit Logs'); ?>
<?php $__env->startSection('page-subtitle', 'Full activity history of the system.'); ?>
<?php $activeView = 'audit-logs'; ?>

<?php $__env->startSection('content'); ?>


<form method="GET" action="<?php echo e(route('admin.audit-logs.index')); ?>" class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm p-5 mb-6 border border-gray-100 dark:border-white/10 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 mb-1 uppercase tracking-wide">Actor Type</label>
        <select name="actor_type" class="border border-gray-300 dark:border-white/20 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary">
            <option value="">All</option>
            <option value="admin"  <?php echo e(request('actor_type') === 'admin'  ? 'selected' : ''); ?>>Admin</option>
            <option value="voter"  <?php echo e(request('actor_type') === 'voter'  ? 'selected' : ''); ?>>Voter</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 mb-1 uppercase tracking-wide">Action Contains</label>
        <input type="text" name="action" value="<?php echo e(request('action')); ?>"
            placeholder="e.g. vote, login"
            class="border border-gray-300 dark:border-white/20 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary w-48">
    </div>
    <button type="submit" class="bg-primary text-white text-sm font-semibold px-5 py-2 rounded-xl hover:bg-secondary transition-colors">
        Filter
    </button>
    <a href="<?php echo e(route('admin.audit-logs.index')); ?>" class="text-sm text-gray-400 dark:text-white/40 hover:text-gray-700 hover:dark:text-white/80 py-2">Clear</a>
</form>


<div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm border border-gray-100 dark:border-white/10 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-white/5 text-xs uppercase text-gray-500 dark:text-white/50 tracking-wide border-b border-gray-200 dark:border-white/10">
                    <th class="px-5 py-3 text-left">#</th>
                    <th class="px-5 py-3 text-left">Action</th>
                    <th class="px-5 py-3 text-left">Actor</th>
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-left">Details</th>
                    <th class="px-5 py-3 text-left">IP Address</th>
                    <th class="px-5 py-3 text-left">Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $badgeColor = match(true) {
                        str_contains($log->action, 'failed')   => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                        str_contains($log->action, 'deleted')  => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                        str_contains($log->action, 'rejected') => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                        str_contains($log->action, 'reset')    => 'bg-secondary/15 dark:bg-secondary/20 text-secondary dark:text-gold-bright',
                        str_contains($log->action, 'login')    => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-gold-bright',
                        str_contains($log->action, 'vote')     => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                        str_contains($log->action, 'approved') => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                        default                                => 'bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-white/70',
                    };
                    $details = $log->details ? json_decode($log->details, true) : [];
                ?>
                <tr class="hover:bg-gray-50 hover:dark:bg-white/5 transition-colors">
                    <td class="px-5 py-3 text-gray-400 dark:text-white/40 text-xs"><?php echo e($log->id); ?></td>
                    <td class="px-5 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?php echo e($badgeColor); ?>">
                            <?php echo e(str_replace('_', ' ', $log->action)); ?>

                        </span>
                    </td>
                    <td class="px-5 py-3 font-medium text-gray-800 dark:text-white/90"><?php echo e($log->actor_name); ?></td>
                    <td class="px-5 py-3">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                            <?php echo e($log->actor_type === 'admin' ? 'bg-primary/10 dark:bg-white/10 text-primary dark:text-parchment' : 'bg-secondary/15 dark:bg-secondary/20 text-secondary dark:text-gold-bright'); ?>">
                            <?php echo e(ucfirst($log->actor_type)); ?>

                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-500 dark:text-white/50 text-xs max-w-xs">
                        <?php if($details): ?>
                            <details class="cursor-pointer">
                                <summary class="text-primary dark:text-parchment hover:underline text-xs">View details</summary>
                                <pre class="mt-1 text-[10px] bg-gray-50 dark:bg-white/5 p-2 rounded overflow-auto max-h-32"><?php echo e(json_encode($details, JSON_PRETTY_PRINT)); ?></pre>
                            </details>
                        <?php else: ?>
                            <span class="text-gray-300 dark:text-white/30">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 text-gray-400 dark:text-white/40 text-xs font-mono"><?php echo e($log->ip_address ?? '—'); ?></td>
                    <td class="px-5 py-3 text-gray-400 dark:text-white/40 text-xs whitespace-nowrap">
                        <?php echo e(\Carbon\Carbon::parse($log->created_at)->timezone('Asia/Manila')->format('M j, Y g:i:s A')); ?>

                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center text-gray-400 dark:text-white/40">No audit logs found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($logs->hasPages()): ?>
    <div class="px-5 py-4 border-t border-gray-100 dark:border-white/10">
        <?php echo e($logs->links()); ?>

    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\JULFAHAD_SSG_EVOTING\resources\views/admin/audit-logs.blade.php ENDPATH**/ ?>
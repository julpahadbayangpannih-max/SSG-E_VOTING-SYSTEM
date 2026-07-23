<?php $__env->startSection('page-title', 'Candidates Management'); ?>
<?php $__env->startSection('page-subtitle', 'Manage all candidates and their affiliations.'); ?>
<?php $activeView = 'candidates'; ?>

<?php $__env->startSection('content'); ?>

<?php if($election): ?>
<div class="mb-4 px-4 py-3 rounded-xl text-sm flex items-center justify-between
    <?php echo e($election->isEditable() ? 'bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-gold-bright' : 'bg-gray-100 dark:bg-white/10 border border-gray-200 dark:border-white/10 text-gray-600 dark:text-white/70'); ?>">
    <span>Managing: <strong><?php echo e($election->title); ?></strong> (<?php echo e($election->status); ?>)
        <?php if(!$election->isEditable()): ?> — read-only, this election is closed. <?php endif; ?>
    </span>
    <a href="<?php echo e(route('admin.elections.index')); ?>" class="text-xs font-semibold underline">Switch election</a>
</div>
<?php else: ?>
<div class="mb-4 px-4 py-3 rounded-xl text-sm bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800/40 text-yellow-800 dark:text-yellow-300">
    No election exists yet. <a href="<?php echo e(route('admin.elections.index')); ?>" class="underline font-semibold">Create one</a> before adding candidates.
</div>
<?php endif; ?>

<div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm border border-gray-100 dark:border-white/10 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
        <h2 class="font-bold text-primary dark:text-parchment">All Candidates</h2>
        <?php if($election && $election->isEditable()): ?>
        <button onclick="openModal()"
            class="bg-primary hover:bg-secondary text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow">
            + Add Candidate
        </button>
        <?php endif; ?>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Photo</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Party List</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Position</th>
                    <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                <?php $__empty_1 = true; $__currentLoopData = $candidates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 hover:dark:bg-white/5 transition-colors" id="cand-row-<?php echo e($c->id); ?>">
                    <td class="px-6 py-4">
                        <?php if($c->image_url): ?>
                            <img src="<?php echo e($c->image_url); ?>" class="h-10 w-10 rounded-full object-cover border border-gray-200 dark:border-white/10">
                        <?php else: ?>
                            <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-white/10 flex items-center justify-center text-gray-600 dark:text-white/70 font-bold text-sm">
                                <?php echo e(strtoupper(substr($c->name, 0, 1))); ?>

                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 font-medium text-sm text-primary dark:text-parchment"><?php echo e($c->name); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-white/70"><?php echo e($c->party_list ?? '—'); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-white/70"><?php echo e($c->position->name ?? '—'); ?></td>
                    <td class="px-6 py-4 text-center space-x-3">
                        <?php if($election && $election->isEditable()): ?>
                        <button onclick="openModal(<?php echo e(json_encode(['id'=>$c->id,'name'=>$c->name,'party_list'=>$c->party_list,'position_id'=>$c->position_id])); ?>)"
                            class="text-blue-600 dark:text-gold-bright hover:text-blue-800 hover:dark:text-gold-bright text-xs font-semibold">Edit</button>
                        <button onclick="deleteCandidate(<?php echo e($c->id); ?>, '<?php echo e(addslashes($c->name)); ?>')"
                            class="text-red-500 dark:text-red-400 hover:text-red-700 hover:dark:text-red-300 text-xs font-semibold">Delete</button>
                        <?php else: ?>
                        <span class="text-xs text-gray-400 dark:text-white/40">read-only</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400 dark:text-white/40 text-sm">No candidates yet. Add positions first.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<div id="cand-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10">
            <h3 class="font-bold text-primary dark:text-parchment" id="modal-title">Add Candidate</h3>
            <button onclick="closeModal()" class="text-gray-400 dark:text-white/40 hover:text-gray-600 hover:dark:text-white/70">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form id="cand-form" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="_method" id="form-method" value="POST">
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Full Name</label>
                    <input type="text" name="name" id="c-name"
                        class="border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary"
                        placeholder="Juan Dela Cruz" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Party List</label>
                    <input type="text" name="party_list" id="c-party_list"
                        class="border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary"
                        placeholder="Independent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Position</label>
                    <select name="position_id" id="c-position_id"
                        class="border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary" required>
                        <option value="">— Select Position —</option>
                        <?php $__currentLoopData = $positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->id); ?>"><?php echo e($p->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Photo (optional, max 2MB)</label>
                    <input type="file" name="image" id="c-image" accept="image/*"
                        class="border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2 w-full focus:outline-none">
                    <p class="text-xs text-gray-400 dark:text-white/40 mt-1">Leave blank to keep existing photo when editing.</p>
                </div>
                <p id="modal-error" class="text-red-600 dark:text-red-400 text-xs hidden"></p>
            </div>
            <div class="flex justify-end gap-3 p-6 border-t border-gray-100 dark:border-white/10">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm text-gray-600 dark:text-white/70 border border-gray-300 dark:border-white/20 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm bg-primary hover:bg-secondary text-white font-semibold rounded-xl transition">Save</button>
            </div>
        </form>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function openModal(data = null) {
    document.getElementById('cand-modal').classList.remove('hidden');
    document.getElementById('modal-title').textContent = data ? 'Edit Candidate' : 'Add Candidate';
    document.getElementById('modal-error').classList.add('hidden');

    const form = document.getElementById('cand-form');
    if (data) {
        form.action = `/admin/candidates/${data.id}`;
        document.getElementById('form-method').value = 'POST'; // Laravel method spoofing handled via hidden _method
        form.querySelector('[name="_method"]').value = 'POST'; // We route POST for file upload
    } else {
        form.action = '<?php echo e(route("admin.candidates.store")); ?>';
        document.getElementById('form-method').value = 'POST';
    }

    document.getElementById('c-name').value        = data?.name ?? '';
    document.getElementById('c-party_list').value  = data?.party_list ?? '';
    document.getElementById('c-position_id').value = data?.position_id ?? '';
    document.getElementById('c-image').value       = '';
}
function closeModal() { document.getElementById('cand-modal').classList.add('hidden'); }

document.getElementById('cand-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const res = await fetch(this.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.csrfToken },
        body: formData,
    });
    const data = await res.json();
    if (data.success) { closeModal(); location.reload(); }
    else {
        const err = document.getElementById('modal-error');
        err.textContent = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Error saving.');
        err.classList.remove('hidden');
    }
});

async function deleteCandidate(id, name) {
    if (!(await confirmDialog(`Delete candidate "${name}"? This cannot be undone.`, { danger: true }))) return;
    const res = await apiFetch(`/admin/candidates/${id}`, { method: 'DELETE' });
    if (res.success) document.getElementById(`cand-row-${id}`)?.remove();
    else await alertDialog(res.message, { danger: true });
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\JULFAHAD_SSG_EVOTING\resources\views/admin/candidates.blade.php ENDPATH**/ ?>
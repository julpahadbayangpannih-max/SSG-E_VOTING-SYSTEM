<?php $__env->startSection('page-title', 'Voters Management'); ?>
<?php $__env->startSection('page-subtitle', 'Manage student registration and voting status.'); ?>
<?php $activeView = 'voters'; ?>

<?php $__env->startSection('content'); ?>


<?php $pending = $voters->where('is_approved', false); ?>
<?php if($pending->count() > 0): ?>
<div class="animate-settle bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800/40 rounded-2xl p-4 mb-6">
    <h3 class="font-bold text-yellow-800 dark:text-yellow-300 mb-3 text-sm">
        <span class="pulse-attention inline-block">⏳</span> Pending Registrations (<?php echo e($pending->count()); ?>)
    </h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-xs uppercase text-yellow-700 dark:text-yellow-300">
                    <th class="text-left px-3 py-2">Student ID</th>
                    <th class="text-left px-3 py-2">Name</th>
                    <th class="text-left px-3 py-2">Course</th>
                    <th class="text-center px-3 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $pending; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="border-t border-yellow-100 dark:border-yellow-800/40">
                    <td class="px-3 py-2 font-mono text-xs"><?php echo e($v->student_id); ?></td>
                    <td class="px-3 py-2"><?php echo e($v->name); ?></td>
                    <td class="px-3 py-2"><?php echo e($v->course); ?></td>
                    <td class="px-3 py-2 text-center space-x-2">
                        <button onclick="approveVoter(<?php echo e($v->id); ?>, this)"
                            class="text-xs bg-green-600 dark:bg-green-600 text-white px-3 py-1 rounded-lg hover:bg-green-700 hover:dark:bg-green-700 transition">Approve</button>
                        <button onclick="rejectVoter(<?php echo e($v->id); ?>, this)"
                            class="text-xs bg-red-500 dark:bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-600 hover:dark:bg-red-600 transition">Reject</button>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


<div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-sm border border-gray-100 dark:border-white/10 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
        <div>
            <h2 class="font-bold text-primary dark:text-parchment">Approved Voters</h2>
            <?php if($election): ?>
                <p class="text-xs text-gray-400 dark:text-white/40 mt-0.5">"Voted" column reflects <strong><?php echo e($election->title); ?></strong>.</p>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openImportModal()"
                class="bg-white dark:bg-[#0f2a4a] border border-primary dark:border-secondary text-primary dark:text-parchment hover:bg-primary hover:text-white dark:hover:bg-secondary dark:hover:text-primary text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow-sm inline-flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 7.5L12 3m0 0L7.5 7.5M12 3v13.5" />
                </svg>
                Import CSV
            </button>
            <button onclick="openVoterModal()"
                class="bg-primary hover:bg-secondary text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow">
                + Add Voter
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10" id="voters-table">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Student ID</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Course</th>
                    <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Voted</th>
                    <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-widest text-primary dark:text-parchment bg-slate-50 dark:bg-white/10">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-white/5" id="voters-tbody">
                <?php $__empty_1 = true; $__currentLoopData = $voters->where('is_approved', true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 hover:dark:bg-white/5 transition-colors" id="voter-row-<?php echo e($voter->id); ?>">
                    <td class="px-6 py-4 text-xs font-mono text-gray-500 dark:text-white/50"><?php echo e($voter->student_id); ?></td>
                    <td class="px-6 py-4 font-medium text-sm"><?php echo e($voter->name); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-white/70"><?php echo e($voter->course); ?></td>
                    <td class="px-6 py-4 text-center">
                        <?php if(in_array($voter->id, $votedIds)): ?>
                            <span class="inline-block bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs font-bold px-2 py-1 rounded-full">Voted</span>
                        <?php else: ?>
                            <span class="inline-block bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-white/50 text-xs px-2 py-1 rounded-full">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center space-x-3">
                        <button onclick="openVoterModal(<?php echo e(json_encode(['id'=>$voter->id,'student_id'=>$voter->student_id,'name'=>$voter->name,'course'=>$voter->course])); ?>)"
                            class="text-blue-600 dark:text-gold-bright hover:text-blue-800 hover:dark:text-gold-bright text-xs font-semibold transition">Edit</button>
                        <button onclick="deleteVoter(<?php echo e($voter->id); ?>, '<?php echo e(addslashes($voter->name)); ?>')"
                            class="text-red-500 dark:text-red-400 hover:text-red-700 hover:dark:text-red-300 text-xs font-semibold transition">Delete</button>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400 dark:text-white/40 text-sm">No approved voters yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<div id="voter-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10">
            <h3 class="font-bold text-primary dark:text-parchment" id="modal-title">Add Voter</h3>
            <button onclick="closeModal()" class="text-gray-400 dark:text-white/40 hover:text-gray-600 hover:dark:text-white/70">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="voter-id">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Student ID</label>
                <input type="text" id="v-student_id" class="border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary" placeholder="2021-00001">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Full Name</label>
                <input type="text" id="v-name" class="border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary" placeholder="Juan Dela Cruz">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-white/70 mb-1">Course</label>
                <input type="text" id="v-course" class="border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full focus:outline-none focus:ring-2 focus:ring-secondary" placeholder="BSIS">
            </div>
            <p id="modal-error" class="text-red-600 dark:text-red-400 text-xs hidden"></p>
        </div>
        <div class="flex justify-end gap-3 p-6 border-t border-gray-100 dark:border-white/10">
            <button onclick="closeModal()" class="px-4 py-2 text-sm text-gray-600 dark:text-white/70 hover:text-gray-800 hover:dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-xl transition">Cancel</button>
            <button onclick="saveVoter()" id="voter-save-btn" class="px-5 py-2 text-sm bg-primary hover:bg-secondary text-white font-semibold rounded-xl transition">Save</button>
        </div>
    </div>
</div>


<div id="import-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#0f2a4a] rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-white/10">
            <h3 class="font-bold text-primary dark:text-parchment">Bulk Import Voters (CSV)</h3>
            <button onclick="closeImportModal()" class="text-gray-400 dark:text-white/40 hover:text-gray-600 hover:dark:text-white/70">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="p-6 space-y-4" id="import-form-area">
            <p class="text-xs text-gray-500 dark:text-white/50">
                CSV must have a header row with columns <code class="bg-gray-100 dark:bg-white/10 px-1 rounded">student_id, name, course</code> (any order).
                Existing student IDs are skipped, not overwritten.
            </p>
            <button type="button" onclick="downloadCsvTemplate()" class="text-xs text-blue-600 dark:text-gold-bright hover:text-blue-800 hover:dark:text-gold-bright font-semibold underline">
                Download CSV template
            </button>
            <input type="file" id="import-file" accept=".csv,text/csv" class="border border-gray-300 dark:border-white/20 rounded-lg text-sm p-2.5 w-full">
            <p id="import-error" class="text-red-600 dark:text-red-400 text-xs hidden"></p>
        </div>
        <div id="import-results-area" class="hidden p-6 border-t border-gray-100 dark:border-white/10 space-y-3">
            <p class="text-sm font-semibold text-gray-700 dark:text-white/80" id="import-summary"></p>
            <div class="max-h-52 overflow-y-auto border rounded-lg">
                <table class="min-w-full text-xs">
                    <thead><tr class="bg-slate-50 dark:bg-white/10">
                        <th class="text-left px-3 py-2">Student ID</th>
                        <th class="text-left px-3 py-2">Name</th>
                        <th class="text-left px-3 py-2">Temp Password</th>
                    </tr></thead>
                    <tbody id="import-created-tbody"></tbody>
                </table>
            </div>
            <button type="button" onclick="downloadImportedCredentials()" class="text-xs bg-primary text-white px-3 py-1.5 rounded-lg font-semibold inline-flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" />
                </svg>
                Download credentials CSV
            </button>
        </div>
        <div class="flex justify-end gap-3 p-6 border-t border-gray-100 dark:border-white/10">
            <button onclick="closeImportModal()" class="px-4 py-2 text-sm text-gray-600 dark:text-white/70 hover:text-gray-800 hover:dark:text-white/90 border border-gray-300 dark:border-white/20 rounded-xl transition">Close</button>
            <button onclick="submitImport()" id="import-submit-btn" class="px-5 py-2 text-sm bg-primary hover:bg-secondary text-white font-semibold rounded-xl transition">Import</button>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function openVoterModal(data = null) {
    document.getElementById('voter-modal').classList.remove('hidden');
    document.getElementById('modal-title').textContent = data ? 'Edit Voter' : 'Add Voter';
    document.getElementById('voter-id').value      = data?.id ?? '';
    document.getElementById('v-student_id').value  = data?.student_id ?? '';
    document.getElementById('v-name').value        = data?.name ?? '';
    document.getElementById('v-course').value      = data?.course ?? '';
    document.getElementById('modal-error').classList.add('hidden');
}
function closeModal() { document.getElementById('voter-modal').classList.add('hidden'); }

async function saveVoter() {
    const id = document.getElementById('voter-id').value;
    const payload = {
        student_id: document.getElementById('v-student_id').value.trim(),
        name:       document.getElementById('v-name').value.trim(),
        course:     document.getElementById('v-course').value.trim(),
    };
    const url    = id ? `/admin/voters/${id}` : '<?php echo e(route("admin.voters.store")); ?>';
    const method = id ? 'PUT' : 'POST';
    const btn    = document.getElementById('voter-save-btn');
    const res    = await withButtonLoading(btn, 'Saving…', () => apiFetch(url, { method, body: JSON.stringify(payload) }));
    if (res.success) {
        closeModal();
        if (res.temp_password) {
            await alertDialog(`Temporary password: ${res.temp_password}\n\nGive this to the student through an official channel — it will not be shown again.`, { title: 'Voter Added' });
        }
        location.reload();
    }
    else {
        const err = document.getElementById('modal-error');
        err.textContent = res.message || (res.errors ? Object.values(res.errors).flat().join(' ') : 'Error saving.');
        err.classList.remove('hidden');
    }
}

async function deleteVoter(id, name) {
    if (!(await confirmDialog(`Delete voter "${name}"? This cannot be undone.`, { danger: true }))) return;
    const res = await apiFetch(`/admin/voters/${id}`, { method: 'DELETE' });
    if (res.success) document.getElementById(`voter-row-${id}`)?.remove();
    else await alertDialog(res.message, { danger: true });
}

async function approveVoter(id, btn) {
    const res = await withButtonLoading(btn, '', () => apiFetch(`/admin/voters/${id}/approve`, { method: 'PATCH' }));
    if (res.success) {
        if (res.temp_password) {
            await alertDialog(`Temporary password: ${res.temp_password}\n\nGive this to the student through an official channel — it will not be shown again.`, { title: 'Voter Approved' });
        }
        location.reload();
    }
    else await alertDialog(res.message, { danger: true });
}

async function rejectVoter(id, btn) {
    if (!(await confirmDialog('Reject and remove this registration?', { danger: true }))) return;
    const res = await withButtonLoading(btn, '', () => apiFetch(`/admin/voters/${id}/reject`, { method: 'DELETE' }));
    if (res.success) location.reload();
    else await alertDialog(res.message, { danger: true });
}

// --- Bulk CSV Import ---
let lastImportedCredentials = [];

function openImportModal() {
    document.getElementById('import-modal').classList.remove('hidden');
    document.getElementById('import-file').value = '';
    document.getElementById('import-error').classList.add('hidden');
    document.getElementById('import-results-area').classList.add('hidden');
    document.getElementById('import-form-area').classList.remove('hidden');
    document.getElementById('import-submit-btn').classList.remove('hidden');
}
function closeImportModal() {
    document.getElementById('import-modal').classList.add('hidden');
    if (lastImportedCredentials.length > 0) location.reload();
}

function downloadCsvTemplate() {
    const csv = 'student_id,name,course\n2021-00001,Juan Dela Cruz,BSIS\n2021-00002,Maria Santos,BSCS\n';
    downloadTextAsFile(csv, 'voters-import-template.csv');
}

function downloadTextAsFile(text, filename) {
    const blob = new Blob([text], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}

async function submitImport() {
    const fileInput = document.getElementById('import-file');
    const errorEl = document.getElementById('import-error');
    errorEl.classList.add('hidden');

    if (!fileInput.files.length) {
        errorEl.textContent = 'Choose a CSV file first.';
        errorEl.classList.remove('hidden');
        return;
    }

    const formData = new FormData();
    formData.append('csv_file', fileInput.files[0]);

    const btn = document.getElementById('import-submit-btn');
    const res = await withButtonLoading(btn, 'Importing…', () =>
        fetch('<?php echo e(route("admin.voters.import")); ?>', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: formData,
        }).then(r => r.json())
    );

    if (!res.success) {
        errorEl.textContent = res.message || 'Import failed.';
        errorEl.classList.remove('hidden');
        return;
    }

    lastImportedCredentials = res.created || [];
    document.getElementById('import-summary').textContent = res.message;
    document.getElementById('import-form-area').classList.add('hidden');
    document.getElementById('import-submit-btn').classList.add('hidden');
    document.getElementById('import-results-area').classList.remove('hidden');

    const tbody = document.getElementById('import-created-tbody');
    // SECURITY: student_id/name come straight from the uploaded CSV, which
    // may have been prepared by someone other than the admin viewing this
    // screen (e.g. handed off by a registrar's office) — treat as untrusted
    // and escape before rendering into innerHTML.
    tbody.innerHTML = lastImportedCredentials.map(c => `
        <tr class="border-t border-gray-100 dark:border-white/10">
            <td class="px-3 py-2 font-mono text-gray-800 dark:text-white/90">${escapeHtml(c.student_id)}</td>
            <td class="px-3 py-2 text-gray-800 dark:text-white/90">${escapeHtml(c.name)}</td>
            <td class="px-3 py-2 font-mono text-gray-800 dark:text-white/90">${escapeHtml(c.temp_password)}</td>
        </tr>
    `).join('') || '<tr><td colspan="3" class="px-3 py-4 text-center text-gray-400 dark:text-white/40">No new voters created.</td></tr>';
}

function downloadImportedCredentials() {
    if (!lastImportedCredentials.length) return;
    let csv = 'student_id,name,course,temp_password\n';
    lastImportedCredentials.forEach(c => {
        csv += `${c.student_id},"${c.name}",${c.course},${c.temp_password}\n`;
    });
    downloadTextAsFile(csv, 'imported-voter-credentials.csv');
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\JULFAHAD_SSG_EVOTING\resources\views/admin/voters.blade.php ENDPATH**/ ?>
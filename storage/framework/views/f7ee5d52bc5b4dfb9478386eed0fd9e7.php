<?php $__env->startSection('title', 'Salary Schedule Requests'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-calendar-alt mr-3 text-blue-600"></i>
                Salary Schedule Requests
            </h1>
            <p class="mt-1 text-sm text-gray-600">Manage your salary schedule preference requests</p>
        </div>
        <?php if($user->role === 'employee'): ?>
        <div class="mt-4 sm:mt-0">
            <a href="<?php echo e(route('salary-schedule.create')); ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                New Request
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Success/Error Messages -->
    <?php if(session('success')): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
        <?php echo e(session('success')); ?>

    </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        <?php echo e(session('error')); ?>

    </div>
    <?php endif; ?>

    <!-- Requests Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <?php if($user->role !== 'employee'): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <?php endif; ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                        <?php if($user->role !== 'employee'): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                        <?php endif; ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50">
                        <?php if($user->role !== 'employee'): ?>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo e($request->employee->first_name); ?> <?php echo e($request->employee->last_name); ?></div>
                            <div class="text-sm text-gray-500"><?php echo e($request->employee->employee_id); ?></div>
                        </td>
                        <?php endif; ?>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900"><?php echo e($request->schedule_type_label); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ];
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo e($statusColors[$request->status]); ?>">
                                <?php echo e($request->status_label); ?>

                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo e($request->created_at->format('M d, Y g:i A')); ?>

                        </td>
                        <?php if($user->role !== 'employee'): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo e($request->approvedBy ? $request->approvedBy->email : '-'); ?>

                        </td>
                        <?php endif; ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if($user->role !== 'employee' && $request->status === 'pending'): ?>
                            <form method="POST" action="<?php echo e(route('salary-schedule.approve', $request->id)); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="text-green-600 hover:text-green-900 mr-3" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <button onclick="openRejectModal('<?php echo e($request->id); ?>')" class="text-red-600 hover:text-red-900 mr-3" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                            <?php endif; ?>
                            <?php if($request->status === 'pending' && ($user->role === 'employee' || $request->employee_id === $user->employee?->id)): ?>
                            <form method="POST" action="<?php echo e(route('salary-schedule.destroy', $request->id)); ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-gray-600 hover:text-gray-900" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e($user->role !== 'employee' ? 6 : 4); ?>" class="px-6 py-4 text-center text-sm text-gray-500">
                            No salary schedule requests found.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if($requests->hasPages()): ?>
        <div class="px-6 py-4 border-t border-gray-200">
            <?php echo e($requests->links()); ?>

        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Request</h3>
            <form method="POST" action="" id="rejectForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="request_id" id="rejectRequestId">
                <div class="mb-4">
                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason</label>
                    <textarea name="rejection_reason" id="rejection_reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRejectModal(id) {
    document.getElementById('rejectRequestId').value = id;
    document.getElementById('rejectForm').action = '<?php echo e(route('salary-schedule.reject', ':id')); ?>'.replace(':id', id);
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
    document.getElementById('rejection_reason').value = '';
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard-base', ['user' => $user, 'activeRoute' => 'salary-schedule.index'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\GR8TECH_Payroll-master\resources\views/salary-schedule/index.blade.php ENDPATH**/ ?>
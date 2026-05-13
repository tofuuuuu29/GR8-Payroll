<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Employee;
use App\Models\Department;

class LeaveController extends Controller
{
    private function canManageBalances($user): bool
    {
        return in_array($user->role, ['admin', 'hr'], true);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Build query
        $query = LeaveRequest::with(['employee.department']);
        
        // Filter by employee (for HR/Admin)
        if ($user->role !== 'employee') {
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
        } else {
            // Employees can only see their own requests
            if ($user->employee) {
                $query->where('employee_id', $user->employee->id);
            }
        }
        
        // Filter by leave type
        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }
        
        // Order by start date descending
        $query->orderBy('start_date', 'desc');
        
        $leaveRequests = $query->paginate(10);
        
        // Calculate summary
        $summary = [
            'total' => LeaveRequest::count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];
        
        // Get employees for filter (for HR/Admin)
        $employees = collect();
        if ($user->role !== 'employee') {
            $employees = Employee::with('department')->get();
        }
        
        // Get leave balances
        $leaveBalances = LeaveBalance::all()->groupBy('employee_id');
        
        // Check if any employees don't have leave balances
        $hasEmployeesWithoutBalances = false;
        if ($user->role !== 'employee') {
            $employeesWithoutBalances = Employee::whereDoesntHave('leaveBalances')->count();
            $hasEmployeesWithoutBalances = $employeesWithoutBalances > 0;
        }
        
        return view("attendance.leave-management", [
            "user" => $user,
            "summary" => $summary,
            "leaveRequests" => $leaveRequests,
            "departments" => Department::all(),
            "employees" => $employees,
            "leaveBalances" => $leaveBalances,
            "statusList" => ["pending","approved","rejected","cancelled"],
            "hasEmployeesWithoutBalances" => $hasEmployeesWithoutBalances
        ]);
    }

    public function exportLeave($format)
    {
        // Basic export implementation
        return back()->with('info', 'Export functionality coming soon');
    }
    
    public function create()
    {
        $user = Auth::user();
        
        // Get employee for the current user
        $employee = $user->employee;
        
        // Get leave balance for the employee
        $leaveBalance = null;
        $availableDays = [];
        
        if ($employee) {
            $currentYear = now()->year;
            $leaveBalance = LeaveBalance::where('employee_id', $employee->id)
                ->where('year', $currentYear)
                ->first();
            
            if ($leaveBalance) {
                $availableDays = [
                    'vacation' => ($leaveBalance->vacation_days_total ?? 0) - ($leaveBalance->vacation_days_used ?? 0),
                    'sick' => ($leaveBalance->sick_days_total ?? 0) - ($leaveBalance->sick_days_used ?? 0),
                    'personal' => ($leaveBalance->personal_days_total ?? 0) - ($leaveBalance->personal_days_used ?? 0),
                    'emergency' => ($leaveBalance->emergency_days_total ?? 0) - ($leaveBalance->emergency_days_used ?? 0),
                    'maternity' => ($leaveBalance->maternity_days_total ?? 0) - ($leaveBalance->maternity_days_used ?? 0),
                    'paternity' => ($leaveBalance->paternity_days_total ?? 0) - ($leaveBalance->paternity_days_used ?? 0),
                    'bereavement' => ($leaveBalance->bereavement_days_total ?? 0) - ($leaveBalance->bereavement_days_used ?? 0),
                    'study' => ($leaveBalance->study_days_total ?? 0) - ($leaveBalance->study_days_used ?? 0),
                ];
            }
        }
        
        // Get employees for HR/Admin
        $employees = collect();
        if (in_array($user->role, ['admin', 'hr'])) {
            $employees = Employee::with('department')->get();
        }
        
        return view("attendance.leave-request-create", [
            "user" => $user,
            "employee" => $employee,
            "leaveBalance" => $leaveBalance,
            "availableDays" => $availableDays,
            "employees" => $employees
        ]);
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->employee) {
            return back()->with('error', 'Employee profile not found');
        }
        
        $validated = $request->validate([
            'leave_type' => 'required|in:vacation,sick,personal,emergency,maternity,paternity,bereavement,study',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
        ]);
        
        // Calculate days requested
        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);
        $daysRequested = $start->diffInDays($end) + 1;
        
        // Check leave balance
        $balance = LeaveBalance::where('employee_id', $user->employee->id)
            ->where('year', now()->year)
            ->first();
        
        $balanceField = $validated['leave_type'] . '_days_total';
        $usedField = $validated['leave_type'] . '_days_used';
        
        if (!$balance || ($balance->$balanceField - $balance->$usedField) < $daysRequested) {
            return back()->with('error', 'Insufficient leave balance for this type');
        }
        
        LeaveRequest::create([
            'employee_id' => $user->employee->id,
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_requested' => $daysRequested,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);
        
        return back()->with('success', 'Leave request submitted successfully');
    }
    
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['admin', 'hr', 'manager'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $leaveRequest = LeaveRequest::findOrFail($id);
        
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'nullable|string|max:500',
        ]);
        
        if ($validated['action'] === 'approve') {
            if ($leaveRequest->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave request is already approved',
                ], 422);
            }

            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
            
            // Update leave balance
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('year', $leaveRequest->start_date->year)
                ->first();
            
            if ($balance) {
                $usedField = $leaveRequest->leave_type . '_days_used';
                $balance->increment($usedField, $leaveRequest->days_requested);
            }
        } else {
            $leaveRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'] ?? 'Rejected by manager',
            ]);
        }
        
        $statusMessage = $validated['action'] === 'approve' ? 'approved' : 'rejected';

        return response()->json(['success' => true, 'message' => 'Leave request ' . $statusMessage]);
    }
    
    public function cancel($id)
    {
        $user = Auth::user();
        
        $leaveRequest = LeaveRequest::findOrFail($id);
        
        // Only employee who created the request can cancel it
        if ($user->role === 'employee' && $leaveRequest->employee_id !== $user->employee?->id) {
            return back()->with('error', 'Unauthorized to cancel this request');
        }
        
        // Can only cancel pending requests
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Can only cancel pending requests');
        }
        
        $leaveRequest->update(['status' => 'cancelled']);
        
        return back()->with('success', 'Leave request cancelled successfully');
    }
    
    public function getLeaveBalance(Request $request)
    {
        $user = Auth::user();
        if (!$this->canManageBalances($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $employeeId = $request->employee_id;
        $year = $request->year ?? now()->year;
        
        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->first();
        
        return response()->json($balance ?? []);
    }
    
    public function storeBalance(Request $request)
    {
        $user = Auth::user();
        if (!$this->canManageBalances($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'employee_id' => 'required',
            'year' => 'required|integer',
            'vacation_days_total' => 'nullable|integer|min:0',
            'sick_days_total' => 'nullable|integer|min:0',
            'personal_days_total' => 'nullable|integer|min:0',
            'emergency_days_total' => 'nullable|integer|min:0',
            'maternity_days_total' => 'nullable|integer|min:0',
            'paternity_days_total' => 'nullable|integer|min:0',
            'bereavement_days_total' => 'nullable|integer|min:0',
            'study_days_total' => 'nullable|integer|min:0',
        ]);
        
        LeaveBalance::updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'year' => $validated['year'],
            ],
            [
                'vacation_days_total' => $validated['vacation_days_total'] ?? 0,
                'sick_days_total' => $validated['sick_days_total'] ?? 0,
                'personal_days_total' => $validated['personal_days_total'] ?? 0,
                'emergency_days_total' => $validated['emergency_days_total'] ?? 0,
                'maternity_days_total' => $validated['maternity_days_total'] ?? 0,
                'paternity_days_total' => $validated['paternity_days_total'] ?? 0,
                'bereavement_days_total' => $validated['bereavement_days_total'] ?? 0,
                'study_days_total' => $validated['study_days_total'] ?? 0,
            ]
        );
        
        return back()->with('success', 'Leave balance set successfully');
    }
    
    public function updateBalance(Request $request, $id)
    {
        $user = Auth::user();
        if (!$this->canManageBalances($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'vacation_days_total' => 'nullable|integer|min:0',
            'sick_days_total' => 'nullable|integer|min:0',
            'personal_days_total' => 'nullable|integer|min:0',
            'emergency_days_total' => 'nullable|integer|min:0',
            'maternity_days_total' => 'nullable|integer|min:0',
            'paternity_days_total' => 'nullable|integer|min:0',
            'bereavement_days_total' => 'nullable|integer|min:0',
            'study_days_total' => 'nullable|integer|min:0',
        ]);
        
        $balance = LeaveBalance::findOrFail($id);
        $balance->update([
            'vacation_days_total' => $validated['vacation_days_total'] ?? $balance->vacation_days_total,
            'sick_days_total' => $validated['sick_days_total'] ?? $balance->sick_days_total,
            'personal_days_total' => $validated['personal_days_total'] ?? $balance->personal_days_total,
            'emergency_days_total' => $validated['emergency_days_total'] ?? $balance->emergency_days_total,
            'maternity_days_total' => $validated['maternity_days_total'] ?? $balance->maternity_days_total,
            'paternity_days_total' => $validated['paternity_days_total'] ?? $balance->paternity_days_total,
            'bereavement_days_total' => $validated['bereavement_days_total'] ?? $balance->bereavement_days_total,
            'study_days_total' => $validated['study_days_total'] ?? $balance->study_days_total,
        ]);
        
        return back()->with('success', 'Leave balance updated successfully');
    }
    
    public function getStatistics()
    {
        $stats = [
            'total_requests' => LeaveRequest::count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
            'by_type' => LeaveRequest::selectRaw('leave_type, COUNT(*) as count')
                ->groupBy('leave_type')
                ->get()
                ->pluck('count', 'leave_type')
                ->toArray(),
        ];
        
        return response()->json($stats);
    }
}

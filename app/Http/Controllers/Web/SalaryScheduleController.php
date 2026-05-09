<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SalaryScheduleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role === 'employee') {
            // Employees can only see their own requests
            $requests = SalaryScheduleRequest::where('employee_id', $user->employee->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            // HR and Admin can see all requests
            $requests = SalaryScheduleRequest::with(['employee', 'approvedBy'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }
        
        return view('salary-schedule.index', [
            'user' => $user,
            'requests' => $requests
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        
        // Check if employee already has a pending request
        $existingRequest = SalaryScheduleRequest::where('employee_id', $user->employee->id)
            ->where('status', 'pending')
            ->first();
        
        if ($existingRequest) {
            return redirect()->route('salary-schedule.index')
                ->with('error', 'You already have a pending request. Please wait for approval before submitting another.');
        }
        
        return view('salary-schedule.create', ['user' => $user]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'schedule_type' => 'required|in:semi_monthly,monthly',
        ]);

        $user = Auth::user();
        
        // Check if employee already has a pending request
        $existingRequest = SalaryScheduleRequest::where('employee_id', $user->employee->id)
            ->where('status', 'pending')
            ->first();
        
        if ($existingRequest) {
            return redirect()->route('salary-schedule.index')
                ->with('error', 'You already have a pending request.');
        }

        SalaryScheduleRequest::create([
            'employee_id' => $user->employee->id,
            'schedule_type' => $request->schedule_type,
            'status' => 'pending',
        ]);

        return redirect()->route('salary-schedule.index')
            ->with('success', 'Your salary schedule request has been submitted and is pending approval.');
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $salaryRequest = SalaryScheduleRequest::findOrFail($id);
        
        if ($salaryRequest->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'This request has already been processed.');
        }

        $salaryRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('salary-schedule.index')
            ->with('success', 'Salary schedule request has been approved.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $salaryRequest = SalaryScheduleRequest::findOrFail($id);
        
        if ($salaryRequest->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'This request has already been processed.');
        }

        $salaryRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->route('salary-schedule.index')
            ->with('success', 'Salary schedule request has been rejected.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $salaryRequest = SalaryScheduleRequest::findOrFail($id);
        
        // Only employees can delete their own pending requests
        if ($user->role === 'employee' && $salaryRequest->employee_id !== $user->employee->id) {
            return redirect()->back()
                ->with('error', 'You can only delete your own requests.');
        }
        
        // Only pending requests can be deleted
        if ($salaryRequest->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Only pending requests can be deleted.');
        }

        $salaryRequest->delete();

        return redirect()->route('salary-schedule.index')
            ->with('success', 'Request has been deleted.');
    }
}

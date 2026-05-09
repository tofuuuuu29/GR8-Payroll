<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $summary = ["total" => 0, "approved" => 0, "pending" => 0, "rejected" => 0];
        $leaveRequests = \App\Models\AttendanceRecord::where("id", -1)->paginate(10);
        $departments = \App\Models\Department::all();
        $employees = \App\Models\Employee::all();
        $leaveBalances = collect(); // Empty collection for now

        return view("attendance.leave-management", [
            "user" => Auth::user(),
            "summary" => $summary,
            "leaveRequests" => $leaveRequests,
            "departments" => $departments,
            "employees" => $employees,
            "leaveBalances" => $leaveBalances,
            "statusList" => ["pending","approved","rejected","cancelled"]
        ]);
    }

    public function exportLeave($format) { return back(); }
    public function create() { return view("attendance.leave-request-create", ["user" => Auth::user()]); }
    public function store(Request $request) { return back(); }
    public function updateStatus(Request $request, $id) { return back(); }
    public function cancel($id) { return back(); }
    public function getLeaveBalance() { return response()->json([]); }
    public function storeBalance(Request $request) { return back(); }
    public function updateBalance(Request $request, $id) { return back(); }
    public function getStatistics() { return response()->json([]); }
}

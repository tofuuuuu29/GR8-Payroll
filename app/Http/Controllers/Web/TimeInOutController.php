<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AttendanceRecord;
use App\Models\EmployeeBreak;
use App\Models\TimeEntry;
use Illuminate\Validation\ValidationException;

class TimeInOutController extends Controller
{
    public function index(Request $request)
    {
        return view('attendance.time-in-out', ['user' => Auth::user()]);
    }

    public function timeIn(Request $request)
    {
        $employee = Auth::user()?->employee;
        if (!$employee) {
            return response()->json(['error' => 'Employee profile not found.'], 422);
        }

        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();

        $attendance = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            $attendance = AttendanceRecord::create([
                'employee_id' => $employee->id,
                'date' => $today,
                'status' => 'present',
                'time_in' => $now,
                'time_out' => null,
            ]);
        }

        $activeEntry = $attendance->timeEntries()->whereNull('time_out')->first();
        if ($activeEntry) {
            return response()->json(['error' => 'You are already clocked in.'], 409);
        }

        $timeEntry = TimeEntry::create([
            'attendance_record_id' => $attendance->id,
            'time_in' => $now,
            'time_out' => null,
            'entry_type' => 'regular',
        ]);

        // Update legacy fields used by some parts of the UI.
        $attendance->update([
            'time_in' => $timeEntry->time_in,
            'time_out' => null,
            'status' => 'present',
        ]);

        return response()->json(['message' => 'Clocked in successfully.'], 200);
    }

    public function timeOut(Request $request)
    {
        $employee = Auth::user()?->employee;
        if (!$employee) {
            return response()->json(['error' => 'Employee profile not found.'], 422);
        }

        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();

        $attendance = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['error' => 'No attendance record for today. Clock in first.'], 422);
        }

        $activeEntry = $attendance->timeEntries()->whereNull('time_out')->first();
        if (!$activeEntry) {
            return response()->json(['error' => 'You are not currently clocked in.'], 409);
        }

        $activeEntry->update([
            'time_out' => $now,
        ]);

        // Recalculate total hours using the new multi-entry model.
        $attendance->load(['timeEntries', 'breaks', 'employee']);

        $attendance->update([
            'time_out' => $now,
            'total_hours' => $attendance->calculateTotalHours(),
            'status' => $attendance->getCalculatedStatus(),
        ]);

        return response()->json(['message' => 'Clocked out successfully.'], 200);
    }

    public function breakStart(Request $request)
    {
        $employee = Auth::user()?->employee;
        if (!$employee) {
            return response()->json(['error' => 'Employee profile not found.'], 422);
        }

        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();

        $attendance = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['error' => 'No attendance record for today. Clock in first.'], 422);
        }

        $activeEntry = $attendance->timeEntries()->whereNull('time_out')->first();
        if (!$activeEntry) {
            return response()->json(['error' => 'You must be clocked in before starting a break.'], 409);
        }

        $activeBreak = $attendance->breaks()->whereNull('break_end')->first();
        if ($activeBreak) {
            return response()->json(['error' => 'Break has already started.'], 409);
        }

        $break = EmployeeBreak::create([
            'attendance_record_id' => $attendance->id,
            'break_start' => $now,
            'break_end' => null,
        ]);

        $attendance->update([
            'break_start' => $break->break_start,
            'break_end' => null,
        ]);

        return response()->json(['message' => 'Break started successfully.', 'is_over_break' => false], 200);
    }

    public function breakEnd(Request $request)
    {
        $employee = Auth::user()?->employee;
        if (!$employee) {
            return response()->json(['error' => 'Employee profile not found.'], 422);
        }

        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();

        $attendance = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['error' => 'No attendance record for today.'], 422);
        }

        $activeBreak = $attendance->breaks()->whereNull('break_end')->first();
        if (!$activeBreak) {
            return response()->json(['error' => 'No active break to end.'], 409);
        }

        $activeBreak->update([
            'break_end' => $now,
        ]);

        $attendance->update([
            'break_end' => $activeBreak->break_end,
        ]);

        return response()->json(['message' => 'Break ended successfully.', 'is_over_break' => false], 200);
    }

    public function getStatus(Request $request)
    {
        $employee = Auth::user()?->employee;
        if (!$employee) {
            return response()->json(['error' => 'Employee profile not found.'], 422);
        }

        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();

        $attendance = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status' => 'not_started',
                'time_in' => null,
                'time_out' => null,
                'break_start' => null,
                'break_end' => null,
                'total_hours' => 0,
                'entry_count' => 0,
                'time_entries' => [],
                'active_time_entry' => null,
                'breaks' => [],
                'active_break' => null,
                'can_time_in' => true,
                'can_time_out' => false,
            ]);
        }

        $timeEntries = $attendance->timeEntries()->orderBy('time_in')->get();
        $activeEntry = $timeEntries->first(fn (TimeEntry $e) => $e->time_out === null);

        $breaks = $attendance->breaks()->orderBy('break_start')->get();
        $activeBreak = $breaks->first(fn (EmployeeBreak $b) => $b->break_end === null);

        // Add is_active so the existing frontend can detect current break.
        $breaksPayload = $breaks->map(function (EmployeeBreak $b) {
            return [
                'id' => $b->id,
                'break_start' => $b->break_start,
                'break_end' => $b->break_end,
                'break_duration_minutes' => $b->break_duration_minutes,
                'is_active' => is_null($b->break_end),
            ];
        })->values();

        return response()->json([
            'status' => $activeEntry ? 'present' : (count($timeEntries) > 0 ? 'completed' : 'not_started'),
            'time_in' => $activeEntry?->time_in ?? $attendance->time_in,
            'time_out' => $activeEntry?->time_out ?? $attendance->time_out,
            'break_start' => $activeBreak?->break_start ?? $attendance->break_start,
            'break_end' => $activeBreak?->break_end ?? $attendance->break_end,
            'total_hours' => $attendance->total_hours ?? 0,
            'entry_count' => $timeEntries->count(),
            'attendance_record' => $attendance,
            'time_entries' => $timeEntries->map(function (TimeEntry $e) {
                return [
                    'id' => $e->id,
                    'attendance_record_id' => $e->attendance_record_id,
                    'time_in' => $e->time_in,
                    'time_out' => $e->time_out,
                    'hours_worked' => $e->hours_worked,
                    'entry_type' => $e->entry_type,
                    'notes' => $e->notes,
                ];
            })->values(),
            'active_time_entry' => $activeEntry ? [
                'id' => $activeEntry->id,
                'attendance_record_id' => $activeEntry->attendance_record_id,
                'time_in' => $activeEntry->time_in,
                'time_out' => $activeEntry->time_out,
                'hours_worked' => $activeEntry->hours_worked,
                'entry_type' => $activeEntry->entry_type,
                'notes' => $activeEntry->notes,
            ] : null,
            'breaks' => $breaksPayload,
            'active_break' => $activeBreak ? [
                'id' => $activeBreak->id,
                'break_start' => $activeBreak->break_start,
                'break_end' => $activeBreak->break_end,
                'break_duration_minutes' => $activeBreak->break_duration_minutes,
                'is_active' => true,
            ] : null,
            'can_time_in' => $activeEntry === null,
            'can_time_out' => $activeEntry !== null,
        ]);
    }
}

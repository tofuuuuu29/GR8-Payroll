<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireTimeInMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is not authenticated, let other middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Allow admin and hr roles to access all modules regardless of time-in status
        if (in_array($user->role, ['admin', 'hr'])) {
            return $next($request);
        }

        // Try to get employee relationship, or find by employee_id if relationship fails
        $employee = $user->employee;
        
        // If relationship is null but employee_id exists, try to find the employee directly
        if (!$employee && $user->employee_id) {
            $employee = \App\Models\Employee::find($user->employee_id);
        }

        // If no employee record, let other middleware handle it
        if (!$employee) {
            return $next($request);
        }

        // Allow access to dashboard, logout, time-in/out UI and API routes, company switching,
        // and forgot-time support routes regardless of time-in status
        if ($request->routeIs([
            'dashboard',
            'logout',
            'attendance.time-in-out',
            'attendance.time-in',
            'attendance.time-out',
            'attendance.break-start',
            'attendance.break-end',
            'attendance.status',
            'attendance.current-time',
            'companies.switch',
            'companies.index',
            'hr.help-support',
            'hr.help-support-ticket-store',
            'hr.profile',
            'hr.profile.update',
            'hr.settings',
            'hr.settings.update',
            'hr.settings.password',
        ])) {
            return $next($request);
        }

        // Check if employee has timed in today AND hasn't timed out yet (legacy row + time_entries)
        $todayAttendance = $employee->getTodayAttendance();

        $hasActiveClockIn = $todayAttendance
            && $todayAttendance->time_in
            && !$todayAttendance->time_out;

        if (!$hasActiveClockIn && $todayAttendance) {
            $todayAttendance->loadMissing('timeEntries');
            $hasActiveClockIn = $todayAttendance->timeEntries
                ->contains(fn ($e) => $e->time_in && $e->time_out === null);
        }

        if (!$hasActiveClockIn) {
            return redirect()->route('dashboard')
                ->with('error', 'You must be currently timed in to access other modules.');
        }

        return $next($request);
    }
}
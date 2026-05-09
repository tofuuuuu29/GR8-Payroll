<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class HrController extends Controller
{
    public function profile(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        $departments = \App\Models\Department::all();
        
        return view('hr.profile', [
            'user' => $user,
            'employee' => $employee,
            'departments' => $departments
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['error' => 'Employee profile not found.'], 422);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:accounts,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'civil_status' => 'nullable|string|max:50',
            'gender' => 'nullable|in:Male,Female,Other',
            'mobile_number' => 'nullable|string|max:20',
            'facebook_link' => 'nullable|url|max:255',
            'linkedin_link' => 'nullable|url|max:255',
            'ig_link' => 'nullable|url|max:255',
            'home_address' => 'nullable|string|max:500',
            'current_address' => 'nullable|string|max:500',
            'emergency_full_name' => 'nullable|string|max:255',
            'emergency_relationship' => 'nullable|string|max:100',
            'emergency_home_address' => 'nullable|string|max:500',
            'emergency_current_address' => 'nullable|string|max:500',
            'emergency_mobile_number' => 'nullable|string|max:20',
            'emergency_email' => 'nullable|email|max:255',
            'emergency_facebook_link' => 'nullable|url|max:255',
        ]);

        try {
            // Update account email
            $user->update(['email' => $validated['email']]);

            // Update employee information
            $employee->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'date_of_birth' => $validated['date_of_birth'],
                'civil_status' => $validated['civil_status'],
                'gender' => $validated['gender'],
                'mobile_number' => $validated['mobile_number'],
                'facebook_link' => $validated['facebook_link'],
                'linkedin_link' => $validated['linkedin_link'],
                'ig_link' => $validated['ig_link'],
                'home_address' => $validated['home_address'],
                'current_address' => $validated['current_address'],
                'emergency_full_name' => $validated['emergency_full_name'],
                'emergency_relationship' => $validated['emergency_relationship'],
                'emergency_home_address' => $validated['emergency_home_address'],
                'emergency_current_address' => $validated['emergency_current_address'],
                'emergency_mobile_number' => $validated['emergency_mobile_number'],
                'emergency_email' => $validated['emergency_email'],
                'emergency_facebook_link' => $validated['emergency_facebook_link'],
            ]);

            return response()->json([
                'message' => 'Profile updated successfully.',
                'user' => $user->fresh(),
                'employee' => $employee->fresh()
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update profile. Please try again.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function settings(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        $departments = \App\Models\Department::all();
        
        return view('hr.settings', [
            'user' => $user,
            'employee' => $employee,
            'departments' => $departments
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'dark_mode' => 'nullable|boolean',
            'timezone' => 'nullable|string|max:50',
            'date_format' => 'nullable|string|max:20',
            'email_notifications' => 'nullable|boolean',
            'auto_save' => 'nullable|boolean',
        ]);

        try {
            // Store preferences in session
            $preferences = session('user_preferences', [
                'timezone' => 'Asia/Manila',
                'date_format' => 'MM/DD/YYYY',
                'dark_mode' => false,
                'email_notifications' => true,
                'auto_save' => true,
            ]);

            // Update preferences with validated data
            foreach ($validated as $key => $value) {
                if ($value !== null) {
                    $preferences[$key] = $value;
                }
            }

            // Save to session
            session(['user_preferences' => $preferences]);

            return response()->json([
                'message' => 'Settings updated successfully.',
                'preferences' => $preferences
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update settings. Please try again.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePassword(Request $request)
    {
        return response()->json(['message' => 'Update password not yet implemented'], 501);
    }

    public function exportData(Request $request)
    {
        return response()->json(['message' => 'Export data not yet implemented'], 501);
    }

    public function backupData(Request $request)
    {
        return response()->json(['message' => 'Backup data not yet implemented'], 501);
    }

    public function getUserSessions(Request $request)
    {
        return view('hr.sessions', ['user' => Auth::user()]);
    }

    public function terminateSession(Request $request, $session)
    {
        return response()->json(['message' => 'Terminate session not yet implemented'], 501);
    }

    public function terminateAllOtherSessions(Request $request)
    {
        return response()->json(['message' => 'Terminate all sessions not yet implemented'], 501);
    }

    public function trackLoginSession(Request $request)
    {
        return response()->json(['message' => 'Track session not yet implemented'], 501);
    }
}

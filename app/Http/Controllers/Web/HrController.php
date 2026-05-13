<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                
                // Store using Laravel's storage system
                $path = $photo->storeAs('profile-photos', $photoName, 'public');
                
                // Delete old photo if exists
                if ($user->photo) {
                    Storage::disk('public')->delete('profile-photos/' . $user->photo);
                }
                
                $user->photo = $photoName;
                $user->save();
            }

            // Update account email
            $user->email = $validated['email'];
            $user->save();

            // Update employee information
            $employee->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
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

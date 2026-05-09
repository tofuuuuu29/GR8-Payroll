<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $currentCompany = \App\Helpers\CompanyHelper::getCurrentCompany();
        $currentCompanyId = $currentCompany ? $currentCompany->id : null;

        // Departments for filter dropdown
        $departmentsQuery = \App\Models\Department::query();
        if ($currentCompany) {
            $departmentsQuery->forCompany($currentCompany->id);
        }
        $departments = $departmentsQuery->orderBy('name')->get();

        // Employees with document count
        $employeesQuery = \App\Models\Employee::with(['department'])
            ->withCount('documents');

        if ($currentCompany) {
            $employeesQuery->forCompany($currentCompany->id);
        }

        // Apply filters
        if ($request->filled('department_id')) {
            $employeesQuery->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            $employeesQuery->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $employeesQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $employees = $employeesQuery->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Companies for admin/HR selector
        $companies = \App\Models\Company::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('documents.index', compact(
            'user',
            'departments',
            'employees',
            'companies',
            'currentCompany',
            'currentCompanyId'
        ));
    }

    public function switchCompany(Request $request)
    {
        return response()->json(['message' => 'Switch company not yet implemented'], 501);
    }

    public function export(Request $request)
    {
        return response()->json(['message' => 'Export not yet implemented'], 501);
    }

    public function exportEmployee(Request $request, $id)
    {
        return response()->json(['message' => 'Export employee not yet implemented'], 501);
    }

    public function getEmployeeDetails(Request $request, $id)
    {
        return response()->json(['employee' => null]);
    }
}

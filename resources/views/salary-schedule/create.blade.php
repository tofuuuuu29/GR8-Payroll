@extends('layouts.dashboard-base', ['user' => $user, 'activeRoute' => 'salary-schedule.create'])

@section('title', 'Request Salary Schedule')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-calendar-alt mr-3 text-blue-600"></i>
                Request Salary Schedule
            </h1>
            <p class="mt-1 text-sm text-gray-600">Submit a request to change your salary schedule preference</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('salary-schedule.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Requests
            </a>
        </div>
    </div>

    <!-- Request Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('salary-schedule.store') }}" class="space-y-6">
            @csrf
            
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Select Your Preferred Schedule</h3>
                
                <div class="space-y-4">
                    <label class="relative flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ old('schedule_type') === 'semi_monthly' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                        <input type="radio" name="schedule_type" value="semi_monthly" {{ old('schedule_type') === 'semi_monthly' ? 'checked' : '' }} class="sr-only peer" required>
                        <div class="flex items-center w-full">
                            <div class="w-5 h-5 border-2 border-gray-300 rounded-full mr-3 peer-checked:border-blue-600 peer-checked:bg-blue-600"></div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">Every 15th of the Month</div>
                                <div class="text-sm text-gray-500">Receive salary twice a month (15th and end of month)</div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="relative flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ old('schedule_type') === 'monthly' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                        <input type="radio" name="schedule_type" value="monthly" {{ old('schedule_type') === 'monthly' ? 'checked' : '' }} class="sr-only peer" required>
                        <div class="flex items-center w-full">
                            <div class="w-5 h-5 border-2 border-gray-300 rounded-full mr-3 peer-checked:border-blue-600 peer-checked:bg-blue-600"></div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">Full Month</div>
                                <div class="text-sm text-gray-500">Receive salary once a month (end of month)</div>
                            </div>
                        </div>
                    </label>
                </div>
                
                @error('schedule_type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">Important Information</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Your request will be reviewed by HR or Admin</li>
                            <li>Approval may take 1-3 business days</li>
                            <li>You will be notified once your request is approved or rejected</li>
                            <li>You can only have one pending request at a time</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('salary-schedule.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Add visual feedback for radio button selection
document.querySelectorAll('input[name="schedule_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('input[name="schedule_type"]').forEach(r => {
            r.closest('label').classList.remove('border-blue-500', 'bg-blue-50');
            r.closest('label').classList.add('border-gray-300');
        });
        
        if (this.checked) {
            this.closest('label').classList.remove('border-gray-300');
            this.closest('label').classList.add('border-blue-500', 'bg-blue-50');
        }
    });
});
</script>
@endsection

<x-guest-layout>
    <form method="POST" action="{{ route('sincregister.post') }}">
    @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="first_name" :value="__('First Name')" />
                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="last_name" :value="__('Last Name')" />
                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="registrant_type" :value="__('Registering As')" />
            <select id="registrant_type" name="registrant_type" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                <option value="" disabled {{ old('registrant_type') ? '' : 'selected' }}>Select one</option>
                <option value="student" {{ old('registrant_type') === 'student' ? 'selected' : '' }}>Student</option>
                <option value="faculty" {{ old('registrant_type') === 'faculty' ? 'selected' : '' }}>Faculty</option>
                <option value="employee_staff" {{ old('registrant_type') === 'employee_staff' ? 'selected' : '' }}>Employee / Staff</option>
            </select>
            <x-input-error :messages="$errors->get('registrant_type')" class="mt-2" />
        </div>

        <div id="student-faculty-fields" class="mt-4">
            <x-input-label for="department_id" :value="__('College / Department')" />
            <select id="department_id" name="department_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                <option value="" disabled {{ old('department_id') ? '' : 'selected' }}>Select your college</option>
                @foreach (\App\Models\Department::orderBy('name')->get() as $department)
                    <option value="{{ $department->id }}" {{ (string) old('department_id') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
        </div>

        <div id="employee-fields" class="mt-4" style="display:none;">
            <div>
                <x-input-label for="employee_office" :value="__('Office / Unit')" />
                <x-text-input id="employee_office" class="block mt-1 w-full" type="text" name="employee_office" :value="old('employee_office')" />
                <x-input-error :messages="$errors->get('employee_office')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="employee_id_number" :value="__('Employee ID Number')" />
                <x-text-input id="employee_id_number" class="block mt-1 w-full" type="text" name="employee_id_number" :value="old('employee_id_number')" />
                <x-input-error :messages="$errors->get('employee_id_number')" class="mt-2" />
            </div>
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const registrantType = document.getElementById('registrant_type');
    const studentFacultyFields = document.getElementById('student-faculty-fields');
    const employeeFields = document.getElementById('employee-fields');
    const departmentId = document.getElementById('department_id');
    const employeeOffice = document.getElementById('employee_office');
    const employeeIdNumber = document.getElementById('employee_id_number');

    function toggleRegistrantFields() {
        if (!registrantType) return;

        const isEmployee = registrantType.value === 'employee_staff';

        studentFacultyFields.style.display = isEmployee ? 'none' : 'block';
        employeeFields.style.display = isEmployee ? 'block' : 'none';

        departmentId.required = !isEmployee;
        employeeOffice.required = isEmployee;
        employeeIdNumber.required = isEmployee;
    }

    if (registrantType) {
        registrantType.addEventListener('change', toggleRegistrantFields);
        toggleRegistrantFields();
    }
});
</script>

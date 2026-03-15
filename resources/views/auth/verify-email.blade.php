<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Your account is not yet verified. Please complete OTP verification from the registration flow before signing in.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <a href="{{ route('sincregister.otp.form') }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ __('Go to OTP Verification') }}
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>

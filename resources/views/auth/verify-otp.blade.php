<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Enter the 6-digit OTP sent to <strong>{{ $email }}</strong> to complete your registration.
    </div>

    @if (session('success'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('sincregister.otp.verify') }}">
        @csrf

        <div>
            <x-input-label for="otp" :value="__('Verification Code (OTP)')" />
            <x-text-input id="otp"
                          class="block mt-1 w-full"
                          type="text"
                          name="otp"
                          :value="old('otp')"
                          maxlength="6"
                          required
                          autofocus
                          autocomplete="one-time-code"
                          placeholder="Enter 6-digit code" />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Verify OTP') }}
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('sincregister.otp.resend') }}" class="mt-4">
        @csrf
        <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Resend OTP
        </button>
    </form>
</x-guest-layout>

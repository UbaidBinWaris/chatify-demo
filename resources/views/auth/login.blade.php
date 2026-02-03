<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Enter your email address to receive a verification code.') }}
    </div>

    <form id="emailForm">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <div id="email-error" class="mt-2 text-sm text-red-600 dark:text-red-400" style="display: none;"></div>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button type="button" id="sendOtpBtn" class="ml-3">
                {{ __('Send OTP') }}
            </x-primary-button>
        </div>
    </form>

    <!-- OTP Verification Form (Hidden initially) -->
    <form id="otpForm" style="display: none;">
        @csrf
        <input type="hidden" id="verified-email" name="email">

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Enter the 6-digit code sent to your email.') }}
        </div>

        <!-- OTP Input -->
        <div>
            <x-input-label for="otp" :value="__('Verification Code')" />
            <x-text-input id="otp" class="block mt-1 w-full" type="text" name="otp" maxlength="6" pattern="[0-9]{6}" required />
            <div id="otp-error" class="mt-2 text-sm text-red-600 dark:text-red-400" style="display: none;"></div>
        </div>

        <div class="flex items-center justify-between mt-4">
            <button type="button" id="resendOtpBtn" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                {{ __('Resend OTP') }}
            </button>

            <x-primary-button type="button" id="verifyOtpBtn">
                {{ __('Verify OTP') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailForm = document.getElementById('emailForm');
            const otpForm = document.getElementById('otpForm');
            const sendOtpBtn = document.getElementById('sendOtpBtn');
            const verifyOtpBtn = document.getElementById('verifyOtpBtn');
            const resendOtpBtn = document.getElementById('resendOtpBtn');
            const emailInput = document.getElementById('email');
            const otpInput = document.getElementById('otp');
            const verifiedEmailInput = document.getElementById('verified-email');

            sendOtpBtn.addEventListener('click', function() {
                sendOtp();
            });

            resendOtpBtn.addEventListener('click', function() {
                sendOtp();
            });

            verifyOtpBtn.addEventListener('click', function() {
                verifyOtp();
            });

            function sendOtp() {
                const email = emailInput.value;
                document.getElementById('email-error').style.display = 'none';

                if (!email) {
                    showError('email-error', 'Please enter your email address.');
                    return;
                }

                sendOtpBtn.disabled = true;
                sendOtpBtn.textContent = 'Sending...';

                fetch('/otp/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        email: email,
                        type: 'login'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        verifiedEmailInput.value = email;
                        emailForm.style.display = 'none';
                        otpForm.style.display = 'block';
                        otpInput.focus();
                    } else {
                        showError('email-error', data.message || 'Failed to send OTP.');
                        sendOtpBtn.disabled = false;
                        sendOtpBtn.textContent = 'Send OTP';
                    }
                })
                .catch(error => {
                    showError('email-error', 'An error occurred. Please try again.');
                    sendOtpBtn.disabled = false;
                    sendOtpBtn.textContent = 'Send OTP';
                });
            }

            function verifyOtp() {
                const otp = otpInput.value;
                const email = verifiedEmailInput.value;
                document.getElementById('otp-error').style.display = 'none';

                if (!otp || otp.length !== 6) {
                    showError('otp-error', 'Please enter a valid 6-digit code.');
                    return;
                }

                verifyOtpBtn.disabled = true;
                verifyOtpBtn.textContent = 'Verifying...';

                fetch('/otp/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        email: email,
                        otp: otp,
                        type: 'login'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        showError('otp-error', data.message || 'Invalid OTP.');
                        verifyOtpBtn.disabled = false;
                        verifyOtpBtn.textContent = 'Verify OTP';
                    }
                })
                .catch(error => {
                    showError('otp-error', 'An error occurred. Please try again.');
                    verifyOtpBtn.disabled = false;
                    verifyOtpBtn.textContent = 'Verify OTP';
                });
            }

            function showError(elementId, message) {
                const errorElement = document.getElementById(elementId);
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
        });
    </script>
</x-guest-layout>

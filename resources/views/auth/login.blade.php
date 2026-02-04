<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Traditional Login Form -->
    <!-- Traditional Login Form -->
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block font-medium text-sm text-gray-300 mb-1">Email</label>
            <input id="email" class="block mt-1 w-full bg-white/5 border border-white/10 rounded-xl focus:border-violet-500 focus:ring-violet-500 text-gray-200 placeholder-gray-500 transition-colors" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password" class="block font-medium text-sm text-gray-300 mb-1">Password</label>
            <input id="password" class="block mt-1 w-full bg-white/5 border border-white/10 rounded-xl focus:border-violet-500 focus:ring-violet-500 text-gray-200 placeholder-gray-500 transition-colors"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center group cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded bg-white/10 border-white/20 text-violet-600 shadow-sm focus:ring-violet-500 transition-colors" name="remember">
                <span class="ml-2 text-sm text-gray-400 group-hover:text-gray-300 transition-colors">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-6">
            @if (Route::has('password.request'))
                <a class="text-sm text-gray-400 hover:text-violet-400 transition-colors duration-200" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif

            <button type="submit" class="ml-3 px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-bold text-sm shadow-lg shadow-violet-600/30 hover:shadow-violet-600/50 hover:scale-105 transition-all duration-300">
                {{ __('Log in') }}
            </button>
        </div>
    </form>

    {{-- OTP-based Login (Commented Out) --}}
    {{--
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
    --}}
</x-guest-layout>

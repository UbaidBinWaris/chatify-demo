<x-guest-layout>
    <div class="mb-6 text-sm text-gray-400 text-center">
        {{ __('Enter your email address to receive a verification code.') }}
    </div>

    <form id="emailForm" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block font-medium text-sm text-gray-300 mb-1">Email</label>
            <input id="email" class="block mt-1 w-full bg-white/5 border border-white/10 rounded-xl focus:border-violet-500 focus:ring-violet-500 text-gray-200 placeholder-gray-500 transition-colors" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <div id="email-error" class="mt-2 text-sm text-red-400" style="display: none;"></div>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-gray-400 hover:text-violet-400 transition-colors duration-200" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <button type="button" id="sendOtpBtn" class="ml-4 px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-bold text-sm shadow-lg shadow-violet-600/30 hover:shadow-violet-600/50 hover:scale-105 transition-all duration-300">
                {{ __('Send OTP') }}
            </button>
        </div>
    </form>

    <!-- OTP Verification Form (Hidden initially) -->
    <form id="otpForm" style="display: none;" class="space-y-6">
        @csrf
        <input type="hidden" id="verified-email" name="email">

        <div class="mb-4 text-sm text-gray-400 text-center">
            {{ __('Enter the 6-digit code sent to your email.') }}
        </div>

        <!-- OTP Input -->
        <div>
            <label for="otp" class="block font-medium text-sm text-gray-300 mb-1">Verification Code</label>
            <input id="otp" class="block mt-1 w-full bg-white/5 border border-white/10 rounded-xl focus:border-violet-500 focus:ring-violet-500 text-gray-200 placeholder-gray-500 transition-colors text-center tracking-widest text-xl" type="text" name="otp" maxlength="6" pattern="[0-9]{6}" required placeholder="000000" />
            <div id="otp-error" class="mt-2 text-sm text-red-400" style="display: none;"></div>
        </div>

        <div class="flex items-center justify-between mt-6">
            <button type="button" id="resendOtpBtn" class="text-sm text-gray-400 hover:text-violet-400 transition-colors duration-200">
                {{ __('Resend OTP') }}
            </button>

            <button type="button" id="verifyOtpBtn" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-bold text-sm shadow-lg shadow-violet-600/30 hover:shadow-violet-600/50 hover:scale-105 transition-all duration-300">
                {{ __('Verify OTP') }}
            </button>
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
                const originalText = sendOtpBtn.textContent;
                sendOtpBtn.textContent = 'Sending...';
                sendOtpBtn.classList.add('opacity-75', 'cursor-not-allowed');

                fetch('/otp/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        email: email,
                        type: 'registration'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        verifiedEmailInput.value = email;
                        
                        // Fade out email form and fade in OTP form
                        emailForm.style.opacity = '0';
                        setTimeout(() => {
                            emailForm.style.display = 'none';
                            otpForm.style.display = 'block';
                            // Trigger reflow
                            otpForm.offsetHeight; 
                            otpForm.style.opacity = '0';
                            otpForm.style.transition = 'opacity 0.5s ease';
                            otpForm.style.opacity = '1';
                            otpInput.focus();
                        }, 300);
                    } else {
                        showError('email-error', data.message || 'Failed to send OTP.');
                        resetBtn(sendOtpBtn, originalText);
                    }
                })
                .catch(error => {
                    showError('email-error', 'An error occurred. Please try again.');
                    resetBtn(sendOtpBtn, originalText);
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
                const originalText = verifyOtpBtn.textContent;
                verifyOtpBtn.textContent = 'Verifying...';
                verifyOtpBtn.classList.add('opacity-75', 'cursor-not-allowed');

                fetch('/otp/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        email: email,
                        otp: otp,
                        type: 'registration'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        showError('otp-error', data.message || 'Invalid OTP.');
                        resetBtn(verifyOtpBtn, originalText);
                    }
                })
                .catch(error => {
                    showError('otp-error', 'An error occurred. Please try again.');
                    resetBtn(verifyOtpBtn, originalText);
                });
            }

            function showError(elementId, message) {
                const errorElement = document.getElementById(elementId);
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }

            function resetBtn(btn, text) {
                btn.disabled = false;
                btn.textContent = text;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        });
    </script>
</x-guest-layout>

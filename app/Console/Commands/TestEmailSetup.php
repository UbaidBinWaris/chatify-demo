<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OtpNotification;

class TestEmailSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration by sending a test OTP email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $testOtp = '123456';

        $this->info("Sending test OTP email to: {$email}");
        $this->info("Test OTP: {$testOtp}");

        try {
            Notification::route('mail', $email)
                ->notify(new OtpNotification($testOtp, 'registration'));

            $this->info('✓ Email sent successfully!');
            $this->info('Please check your inbox (and spam folder).');
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to send email:');
            $this->error($e->getMessage());
            return 1;
        }
    }
}

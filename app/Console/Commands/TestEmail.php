<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? config('mail.from.address');

        $this->info("Sending test email to: {$email}");

        try {
            Mail::raw('This is a test email from your Laravel application. Email configuration is working correctly!', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email - Configuration Success');
            });

            $this->info('✓ Email sent successfully!');
            $this->info('Check your inbox at: ' . $email);
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to send email!');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}

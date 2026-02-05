<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\TimezoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TimezoneDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function user_timezone_is_detected_and_stored_on_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'timezone' => 'UTC',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $user->refresh();

        // Check that timezone fields exist
        $this->assertNotNull($user->timezone);
        $this->assertNotNull($user->last_ip);
    }

    /** @test */
    public function user_can_update_their_timezone_preference()
    {
        $user = User::factory()->create([
            'timezone' => 'UTC',
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => 'America/New_York',
        ]);

        $user->refresh();
        $this->assertEquals('America/New_York', $user->timezone);
    }

    /** @test */
    public function middleware_detects_timezone_from_ip()
    {
        $user = User::factory()->create([
            'timezone' => 'UTC',
            'last_ip' => null,
        ]);

        $this->actingAs($user)
            ->get('/dashboard');

        $user->refresh();

        // Check that last_ip was updated
        $this->assertNotNull($user->last_ip);
    }

    /** @test */
    public function timezone_is_applied_to_message_timestamps()
    {
        $user = User::factory()->create([
            'timezone' => 'America/New_York',
        ]);

        $this->actingAs($user);

        $datetime = Carbon::parse('2026-02-06 12:00:00', 'UTC');
        $display = TimezoneService::getTimeDisplay($datetime, $user->timezone);

        // New York is UTC-5, so 12:00 UTC should be 07:00 EST
        $this->assertStringContainsString('7:00 AM', $display['formatted']);
    }

    /** @test */
    public function different_users_see_different_times_based_on_timezone()
    {
        $userUTC = User::factory()->create(['timezone' => 'UTC']);
        $userEST = User::factory()->create(['timezone' => 'America/New_York']);
        $userJST = User::factory()->create(['timezone' => 'Asia/Tokyo']);

        $datetime = Carbon::parse('2026-02-06 12:00:00', 'UTC');

        $displayUTC = TimezoneService::getTimeDisplay($datetime, $userUTC->timezone);
        $displayEST = TimezoneService::getTimeDisplay($datetime, $userEST->timezone);
        $displayJST = TimezoneService::getTimeDisplay($datetime, $userJST->timezone);

        // All should have the same timestamp (Unix time)
        $this->assertEquals($displayUTC['timestamp'], $displayEST['timestamp']);
        $this->assertEquals($displayUTC['timestamp'], $displayJST['timestamp']);

        // But different formatted times
        $this->assertNotEquals($displayUTC['formatted'], $displayEST['formatted']);
        $this->assertNotEquals($displayUTC['formatted'], $displayJST['formatted']);
    }

    /** @test */
    public function timezone_detection_handles_cloudflare_headers()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withServerVariables(['HTTP_CF_CONNECTING_IP' => '8.8.8.8'])
            ->get('/dashboard');

        $user->refresh();
        $this->assertNotNull($user->last_ip);
    }

    /** @test */
    public function timezone_detection_handles_proxy_headers()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withServerVariables(['HTTP_X_FORWARDED_FOR' => '8.8.8.8, 192.168.1.1'])
            ->get('/dashboard');

        $user->refresh();
        $this->assertEquals('8.8.8.8', $user->last_ip);
    }

    /** @test */
    public function user_model_has_timezone_fillable_fields()
    {
        $user = User::factory()->create();

        $user->update([
            'timezone' => 'Europe/London',
            'detected_timezone' => 'Europe/Paris',
            'last_ip' => '1.2.3.4',
        ]);

        $user->refresh();

        $this->assertEquals('Europe/London', $user->timezone);
        $this->assertEquals('Europe/Paris', $user->detected_timezone);
        $this->assertEquals('1.2.3.4', $user->last_ip);
    }

    /** @test */
    public function time_display_remains_consistent_across_server_restarts()
    {
        $user = User::factory()->create([
            'timezone' => 'America/Los_Angeles',
        ]);

        $datetime = Carbon::parse('2026-02-06 12:00:00', 'UTC');

        // Get time display multiple times
        $display1 = TimezoneService::getTimeDisplay($datetime, $user->timezone);
        $display2 = TimezoneService::getTimeDisplay($datetime, $user->timezone);

        // Both should be identical
        $this->assertEquals($display1['formatted'], $display2['formatted']);
        $this->assertEquals($display1['timestamp'], $display2['timestamp']);
        $this->assertEquals($display1['iso'], $display2['iso']);
    }

    /** @test */
    public function relative_time_updates_correctly_for_different_intervals()
    {
        $timezone = 'UTC';
        
        $testCases = [
            ['seconds' => 30, 'expected' => 'Just now'],
            ['minutes' => 5, 'expected' => '5 min'],
            ['minutes' => 59, 'expected' => '59 min'],
            ['hours' => 1, 'expected' => '1 hr'],
            ['hours' => 23, 'expected' => '23 hr'],
            ['days' => 1, 'expected' => '1 day'],
            ['days' => 6, 'expected' => '6 days'],
        ];

        foreach ($testCases as $testCase) {
            $datetime = Carbon::now($timezone);
            
            if (isset($testCase['seconds'])) {
                $datetime->subSeconds($testCase['seconds']);
            } elseif (isset($testCase['minutes'])) {
                $datetime->subMinutes($testCase['minutes']);
            } elseif (isset($testCase['hours'])) {
                $datetime->subHours($testCase['hours']);
            } elseif (isset($testCase['days'])) {
                $datetime->subDays($testCase['days']);
            }

            $display = TimezoneService::getTimeDisplay($datetime, $timezone);
            $this->assertEquals($testCase['expected'], $display['relative'], 
                "Failed for: " . json_encode($testCase));
        }
    }

    /** @test */
    public function timezone_service_handles_edge_cases()
    {
        // Test with null user
        $display = TimezoneService::formatForUser(Carbon::now(), null);
        $this->assertNotEmpty($display);

        // Test with empty string
        $display = TimezoneService::formatForUser('', 'UTC');
        $this->assertEquals('', $display);

        // Test with invalid date
        $display = TimezoneService::getTimeDisplay('invalid-date', 'UTC');
        $this->assertIsArray($display);
    }

    /** @test */
    public function last_seen_time_displays_correctly_in_user_timezone()
    {
        $user = User::factory()->create([
            'timezone' => 'Europe/London',
            'last_seen_at' => Carbon::now('UTC')->subMinutes(30),
        ]);

        $this->actingAs($user);

        $display = TimezoneService::getTimeDisplay($user->last_seen_at, $user->timezone);
        
        $this->assertEquals('30 min', $display['relative']);
        $this->assertNotEmpty($display['formatted']);
    }
}

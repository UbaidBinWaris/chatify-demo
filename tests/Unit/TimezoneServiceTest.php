<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TimezoneServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function it_detects_private_ip_addresses()
    {
        $privateIps = ['127.0.0.1', '::1', '10.0.0.1', '172.16.0.1', '192.168.1.1'];

        foreach ($privateIps as $ip) {
            $timezone = TimezoneService::detectTimezoneFromIp($ip);
            $this->assertEquals(config('app.timezone', 'UTC'), $timezone, "Failed for IP: {$ip}");
        }
    }

    /** @test */
    public function it_formats_datetime_for_user_timezone()
    {
        $datetime = Carbon::parse('2026-02-06 12:00:00', 'UTC');
        
        // Test UTC formatting
        $formatted = TimezoneService::formatForUser($datetime, 'UTC', 'Y-m-d H:i:s');
        $this->assertEquals('2026-02-06 12:00:00', $formatted);
        
        // Test different timezone
        $formatted = TimezoneService::formatForUser($datetime, 'America/New_York', 'Y-m-d H:i:s');
        $this->assertStringContainsString('2026-02-06', $formatted);
    }

    /** @test */
    public function it_returns_proper_time_display_for_recent_times()
    {
        $timezone = 'UTC';

        // Just now
        $datetime = Carbon::now($timezone)->subSeconds(30);
        $display = TimezoneService::getTimeDisplay($datetime, $timezone);
        $this->assertEquals('Just now', $display['relative']);

        // Minutes ago
        $datetime = Carbon::now($timezone)->subMinutes(5);
        $display = TimezoneService::getTimeDisplay($datetime, $timezone);
        $this->assertEquals('5 min', $display['relative']);

        // Hours ago
        $datetime = Carbon::now($timezone)->subHours(3);
        $display = TimezoneService::getTimeDisplay($datetime, $timezone);
        $this->assertEquals('3 hr', $display['relative']);

        // Days ago
        $datetime = Carbon::now($timezone)->subDays(2);
        $display = TimezoneService::getTimeDisplay($datetime, $timezone);
        $this->assertEquals('2 days', $display['relative']);

        // More than a week ago
        $datetime = Carbon::now($timezone)->subDays(10);
        $display = TimezoneService::getTimeDisplay($datetime, $timezone);
        $this->assertStringContainsString('Jan', $display['relative']);
    }

    /** @test */
    public function it_returns_all_required_time_display_fields()
    {
        $datetime = Carbon::now('UTC');
        $display = TimezoneService::getTimeDisplay($datetime, 'UTC');

        $this->assertArrayHasKey('formatted', $display);
        $this->assertArrayHasKey('iso', $display);
        $this->assertArrayHasKey('relative', $display);
        $this->assertArrayHasKey('timestamp', $display);
    }

    /** @test */
    public function it_handles_null_datetime_gracefully()
    {
        $display = TimezoneService::getTimeDisplay(null, 'UTC');

        $this->assertEquals('', $display['formatted']);
        $this->assertEquals('', $display['iso']);
        $this->assertEquals('', $display['relative']);
    }

    /** @test */
    public function it_provides_common_timezones_list()
    {
        $timezones = TimezoneService::getCommonTimezones();

        $this->assertIsArray($timezones);
        $this->assertArrayHasKey('UTC', $timezones);
        $this->assertArrayHasKey('America/New_York', $timezones);
        $this->assertArrayHasKey('Asia/Tokyo', $timezones);
    }

    /** @test */
    public function it_converts_timezone_to_friendly_name()
    {
        $name = TimezoneService::getFriendlyTimezoneName('America/New_York');
        $this->assertEquals('New York', $name);

        $name = TimezoneService::getFriendlyTimezoneName('Europe/London');
        $this->assertEquals('London', $name);

        $name = TimezoneService::getFriendlyTimezoneName('Asia/Los_Angeles');
        $this->assertEquals('Los Angeles', $name);
    }

    /** @test */
    public function it_formats_time_correctly_across_different_timezones()
    {
        $utcTime = Carbon::parse('2026-02-06 12:00:00', 'UTC');

        // Test multiple timezones
        $timezones = [
            'America/New_York' => -5, // EST offset
            'Europe/London' => 0,     // GMT
            'Asia/Tokyo' => 9,        // JST offset
        ];

        foreach ($timezones as $tz => $offset) {
            $formatted = TimezoneService::formatForUser($utcTime, $tz, 'H');
            $expectedHour = (12 + $offset) % 24;
            
            if ($expectedHour < 0) {
                $expectedHour += 24;
            }

            $this->assertEquals(
                str_pad($expectedHour, 2, '0', STR_PAD_LEFT),
                $formatted,
                "Failed for timezone: {$tz}"
            );
        }
    }

    /** @test */
    public function it_handles_single_day_properly()
    {
        $timezone = 'UTC';
        $datetime = Carbon::now($timezone)->subDay();
        $display = TimezoneService::getTimeDisplay($datetime, $timezone);
        
        $this->assertEquals('1 day', $display['relative']);
    }

    /** @test */
    public function it_caches_timezone_detection_results()
    {
        // First call should make an API request (mocked)
        $ip = '8.8.8.8';
        $timezone1 = TimezoneService::detectTimezoneFromIp($ip);
        
        // Second call should use cache
        $timezone2 = TimezoneService::detectTimezoneFromIp($ip);
        
        $this->assertEquals($timezone1, $timezone2);
        
        // Check that it's in cache
        $cacheKey = "timezone_ip_{$ip}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_validates_timezone_identifier()
    {
        $datetime = Carbon::now();
        
        // Valid timezone
        $display = TimezoneService::getTimeDisplay($datetime, 'America/New_York');
        $this->assertNotEmpty($display['formatted']);
        
        // Invalid timezone should fallback gracefully
        $display = TimezoneService::getTimeDisplay($datetime, 'Invalid/Timezone');
        $this->assertIsArray($display);
    }

    /** @test */
    public function it_returns_iso8601_formatted_datetime()
    {
        $datetime = Carbon::parse('2026-02-06 12:00:00', 'UTC');
        $display = TimezoneService::getTimeDisplay($datetime, 'UTC');
        
        $this->assertStringContainsString('2026-02-06', $display['iso']);
        $this->assertStringContainsString('T', $display['iso']);
    }

    /** @test */
    public function it_returns_unix_timestamp()
    {
        $datetime = Carbon::parse('2026-02-06 12:00:00', 'UTC');
        $display = TimezoneService::getTimeDisplay($datetime, 'UTC');
        
        $this->assertIsInt($display['timestamp']);
        $this->assertEquals($datetime->timestamp, $display['timestamp']);
    }
}

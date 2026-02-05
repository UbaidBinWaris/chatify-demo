<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TimezoneService
{
    /**
     * Detect timezone from IP address
     *
     * @param string $ip
     * @return string|null
     */
    public static function detectTimezoneFromIp($ip)
    {
        // Skip private/local IPs
        if (self::isPrivateIp($ip)) {
            return config('app.timezone', 'UTC');
        }

        // Cache the result for 24 hours
        $cacheKey = "timezone_ip_{$ip}";
        
        return Cache::remember($cacheKey, 86400, function () use ($ip) {
            try {
                // Try ip-api.com (free, no API key required)
                $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['timezone']) && $data['status'] === 'success') {
                        // Validate timezone
                        if (in_array($data['timezone'], timezone_identifiers_list())) {
                            return $data['timezone'];
                        }
                    }
                }

                // Fallback to ipapi.co
                $response = Http::timeout(5)->get("https://ipapi.co/{$ip}/timezone/");
                
                if ($response->successful()) {
                    $timezone = trim($response->body());
                    
                    if (in_array($timezone, timezone_identifiers_list())) {
                        return $timezone;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failed to detect timezone for IP {$ip}: " . $e->getMessage());
            }

            return config('app.timezone', 'UTC');
        });
    }

    /**
     * Check if IP is private/local
     *
     * @param string $ip
     * @return bool
     */
    private static function isPrivateIp($ip)
    {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        // Check for private IP ranges
        $privateRanges = [
            '10.0.0.0' => '10.255.255.255',
            '172.16.0.0' => '172.31.255.255',
            '192.168.0.0' => '192.168.255.255',
        ];

        $ipLong = ip2long($ip);
        
        foreach ($privateRanges as $start => $end) {
            if ($ipLong >= ip2long($start) && $ipLong <= ip2long($end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    public static function getClientIp()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle X-Forwarded-For with multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Format datetime for user's timezone
     *
     * @param mixed $datetime
     * @param string|null $timezone
     * @param string $format
     * @return string
     */
    public static function formatForUser($datetime, $timezone = null, $format = 'Y-m-d H:i:s')
    {
        if (!$datetime) {
            return '';
        }

        try {
            $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
            
            if ($timezone) {
                $carbon = $carbon->timezone($timezone);
            }

            return $carbon->format($format);
        } catch (\Exception $e) {
            Log::warning("Failed to format datetime: " . $e->getMessage());
            return $datetime instanceof Carbon ? $datetime->format($format) : $datetime;
        }
    }

    /**
     * Get human-readable time difference with actual datetime
     * This replaces diffForHumans() with a more accurate timestamp
     *
     * @param mixed $datetime
     * @param string|null $timezone
     * @return array
     */
    public static function getTimeDisplay($datetime, $timezone = null)
    {
        if (!$datetime) {
            return [
                'formatted' => '',
                'iso' => '',
                'relative' => '',
            ];
        }

        try {
            $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
            
            if ($timezone) {
                $carbon = $carbon->timezone($timezone);
            }

            $now = Carbon::now($timezone);
            $diffInMinutes = $now->diffInMinutes($carbon);
            $diffInHours = $now->diffInHours($carbon);
            $diffInDays = $now->diffInDays($carbon);

            // Determine the best format based on how recent the time is
            if ($diffInMinutes < 1) {
                $relative = 'Just now';
            } elseif ($diffInMinutes < 60) {
                $relative = $diffInMinutes . ' min';
            } elseif ($diffInHours < 24) {
                $relative = $diffInHours . ' hr';
            } elseif ($diffInDays < 7) {
                $relative = $diffInDays . ' day' . ($diffInDays > 1 ? 's' : '');
            } else {
                $relative = $carbon->format('M d');
            }

            return [
                'formatted' => $carbon->format('M d, Y g:i A'),
                'iso' => $carbon->toIso8601String(),
                'relative' => $relative,
                'timestamp' => $carbon->timestamp,
            ];
        } catch (\Exception $e) {
            Log::warning("Failed to get time display: " . $e->getMessage());
            return [
                'formatted' => $datetime,
                'iso' => '',
                'relative' => '',
            ];
        }
    }

    /**
     * Convert timezone identifier to a friendly name
     *
     * @param string $timezone
     * @return string
     */
    public static function getFriendlyTimezoneName($timezone)
    {
        $parts = explode('/', $timezone);
        $name = end($parts);
        return str_replace('_', ' ', $name);
    }

    /**
     * Get list of common timezones
     *
     * @return array
     */
    public static function getCommonTimezones()
    {
        return [
            'UTC' => 'UTC',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'America/Anchorage' => 'Alaska',
            'Pacific/Honolulu' => 'Hawaii',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Europe/Berlin' => 'Berlin',
            'Asia/Dubai' => 'Dubai',
            'Asia/Karachi' => 'Karachi',
            'Asia/Kolkata' => 'Mumbai, Kolkata',
            'Asia/Shanghai' => 'Beijing, Shanghai',
            'Asia/Tokyo' => 'Tokyo',
            'Asia/Singapore' => 'Singapore',
            'Australia/Sydney' => 'Sydney',
            'Pacific/Auckland' => 'Auckland',
        ];
    }
}

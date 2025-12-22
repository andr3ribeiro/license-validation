<?php

namespace App\Infrastructure;

/**
 * Utility class for generating unique identifiers
 */
class IdGenerator
{
    /**
     * Generate a UUID v4
     */
    public static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Generate a secure random string
     */
    public static function generateRandomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a license key in format: {BRAND}-{YEAR}-{RANDOM}
     * Example: RANK-2025-A1B2C3D4E5F6
     */
    public static function generateLicenseKey(string $brandAcronym): string
    {
        $year = date('Y');
        $random = strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
        
        return "{$brandAcronym}-{$year}-{$random}";
    }

    /**
     * Generate an API key
     */
    public static function generateApiKey(): string
    {
        return 'sk_' . bin2hex(random_bytes(32));
    }
}

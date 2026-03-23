<?php

namespace Omnipay\Akbank\Helpers;

class Helper
{
    /**
     * Generate HMAC-SHA512 hash of data with secretKey.
     * Returns base64-encoded hash.
     */
    public static function hash(string $data, string $secretKey): string
    {
        return base64_encode(hash_hmac('sha512', $data, $secretKey, true));
    }

    /**
     * Generate HMAC-SHA512 hash of JSON body for API auth-hash header.
     */
    public static function hashJsonBody(string $jsonBody, string $secretKey): string
    {
        return self::hash($jsonBody, $secretKey);
    }

    /**
     * Generate 128-character random hex string for API requests.
     */
    public static function generateRandomNumber(): string
    {
        return bin2hex(random_bytes(64));
    }

    /**
     * Get current datetime in ISO 8601 format for API requests.
     */
    public static function getRequestDateTime(): string
    {
        return date('Y-m-d\TH:i:s.v');
    }

    /**
     * Format amount to decimal string with dot separator (e.g., "1.00").
     *
     * @param string|float|int $amount
     */
    public static function formatAmount($amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * Format card expiry to MMYY format.
     */
    public static function formatExpiry(string $month, string $year): string
    {
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $year = substr($year, -2);

        return $month . $year;
    }

    /**
     * Get currency code as integer.
     *
     * @param string|int $currency
     */
    public static function getCurrencyCode($currency): int
    {
        $map = [
            'TRY' => 949,
            'USD' => 840,
            'EUR' => 978,
            'GBP' => 826,
            'JPY' => 392,
        ];

        if (is_numeric($currency)) {
            return (int) $currency;
        }

        return $map[strtoupper($currency)] ?? 949;
    }
}

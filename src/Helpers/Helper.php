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
     * Fields a payment callback's `hashParams` must cover for the strict check to pass.
     *
     * @var list<string>
     */
    public const PAYMENT_CALLBACK_REQUIRED_HASH_PARAMS = ['orderId', 'amount', 'responseCode'];

    /**
     * Verify the response hash on a secure (3D / 3D Pay / 3D Pay Hosting) callback (doc §6.1.1.2).
     *
     * The bank returns a dynamic `hashParams` (a '+'-separated list of field names) plus a `hash`.
     * The merchant must concatenate the response values in that exact order and compare.
     *
     * Because `hashParams` is itself part of the posted data, the sender decides which fields are
     * signed. Pass `$requiredParams` (e.g. self::PAYMENT_CALLBACK_REQUIRED_HASH_PARAMS) to also
     * require that each of those fields is listed in `hashParams` and present in the response;
     * otherwise fields you rely on (orderId, amount, responseCode) may be unsigned. Without it the
     * method behaves as before (only the hash over whatever `hashParams` lists is checked).
     *
     * @param array<string, mixed> $response The POST/response fields from the bank.
     * @param list<string> $requiredParams Field names that must be covered by the hash.
     */
    public static function verifyResponseHash(array $response, string $secretKey, array $requiredParams = []): bool
    {
        if ($secretKey === '' || empty($response['hashParams']) || empty($response['hash'])) {
            return false;
        }

        $params = array_values(array_filter(
            explode('+', (string) $response['hashParams']),
            static fn (string $param): bool => $param !== ''
        ));

        foreach ($requiredParams as $required) {
            if (! in_array($required, $params, true) || ! array_key_exists($required, $response)) {
                return false;
            }
        }

        $plain = '';
        foreach ($params as $param) {
            $plain .= (string) ($response[$param] ?? '');
        }

        return hash_equals(self::hash($plain, $secretKey), (string) $response['hash']);
    }

    /**
     * Strict callback verification: the hash must be valid and must cover orderId, amount and responseCode.
     *
     * @param array<string, mixed> $response The POST/response fields from the bank.
     */
    public static function verifyPaymentCallbackHash(array $response, string $secretKey): bool
    {
        return self::verifyResponseHash($response, $secretKey, self::PAYMENT_CALLBACK_REQUIRED_HASH_PARAMS);
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

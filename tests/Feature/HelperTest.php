<?php

namespace Omnipay\Akbank\Tests\Feature;

use Omnipay\Akbank\Helpers\Helper;
use Omnipay\Akbank\Tests\TestCase;

class HelperTest extends TestCase
{
    public function test_hmac_sha512_hash()
    {
        $data = 'test-data';
        $secretKey = 'test-secret-key';

        $hash = Helper::hash($data, $secretKey);

        // HMAC-SHA512 should return a base64-encoded string
        $this->assertNotEmpty($hash);

        // Verify it's valid base64
        $decoded = base64_decode($hash, true);
        $this->assertNotFalse($decoded);

        // SHA-512 produces 64 bytes
        $this->assertEquals(64, strlen($decoded));

        // Verify deterministic output
        $hash2 = Helper::hash($data, $secretKey);
        $this->assertEquals($hash, $hash2);
    }

    public function test_hash_json_body()
    {
        $jsonBody = '{"key":"value"}';
        $secretKey = 'test-secret';

        $hash = Helper::hashJsonBody($jsonBody, $secretKey);

        $this->assertNotEmpty($hash);

        // Should equal the same result as hash()
        $expected = Helper::hash($jsonBody, $secretKey);
        $this->assertEquals($expected, $hash);
    }

    public function test_generate_random_number()
    {
        $random = Helper::generateRandomNumber();

        // Must be 128 hex characters
        $this->assertEquals(128, strlen($random));

        // Must be valid hex
        $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $random);

        // Each call should produce a different value
        $random2 = Helper::generateRandomNumber();
        $this->assertNotEquals($random, $random2);
    }

    public function test_format_amount()
    {
        $this->assertEquals('1.00', Helper::formatAmount(1));
        $this->assertEquals('1.00', Helper::formatAmount('1'));
        $this->assertEquals('1.00', Helper::formatAmount(1.0));
        $this->assertEquals('10.50', Helper::formatAmount(10.5));
        $this->assertEquals('10.50', Helper::formatAmount('10.50'));
        $this->assertEquals('0.01', Helper::formatAmount(0.01));
        $this->assertEquals('1234.56', Helper::formatAmount(1234.56));
    }

    public function test_format_expiry()
    {
        // Standard month and 4-digit year
        $this->assertEquals('0126', Helper::formatExpiry('01', '2026'));

        // 2-digit year
        $this->assertEquals('1225', Helper::formatExpiry('12', '25'));

        // Single-digit month
        $this->assertEquals('0126', Helper::formatExpiry('1', '2026'));

        // Month 12
        $this->assertEquals('1230', Helper::formatExpiry('12', '2030'));
    }

    public function test_get_currency_code()
    {
        $this->assertEquals(949, Helper::getCurrencyCode('TRY'));
        $this->assertEquals(840, Helper::getCurrencyCode('USD'));
        $this->assertEquals(978, Helper::getCurrencyCode('EUR'));
        $this->assertEquals(826, Helper::getCurrencyCode('GBP'));
        $this->assertEquals(392, Helper::getCurrencyCode('JPY'));

        // Numeric pass-through
        $this->assertEquals(949, Helper::getCurrencyCode(949));
        $this->assertEquals(840, Helper::getCurrencyCode('840'));

        // Case insensitive
        $this->assertEquals(949, Helper::getCurrencyCode('try'));

        // Unknown defaults to TRY
        $this->assertEquals(949, Helper::getCurrencyCode('UNKNOWN'));
    }

    public function test_get_request_date_time()
    {
        $dateTime = Helper::getRequestDateTime();

        $this->assertNotEmpty($dateTime);

        // Should contain T separator
        $this->assertStringContainsString('T', $dateTime);
    }
}

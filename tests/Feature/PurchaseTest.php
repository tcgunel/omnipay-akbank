<?php

namespace Omnipay\Akbank\Tests\Feature;

use Omnipay\Akbank\Constants\PaymentModel;
use Omnipay\Akbank\Constants\TxnCode;
use Omnipay\Akbank\Helpers\Helper;
use Omnipay\Akbank\Message\PurchaseRequest;
use Omnipay\Akbank\Message\PurchaseResponse;
use Omnipay\Akbank\Tests\TestCase;
use Omnipay\Common\Exception\InvalidRequestException;

class PurchaseTest extends TestCase
{
    /**
     * Test non-3D purchase request data structure.
     */
    public function test_purchase_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest.json');
        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $data = $request->getData();

        // Verify terminal block
        $this->assertEquals('2023090417500272654BD9A49CF07574', $data['terminal']['merchantSafeId']);
        $this->assertEquals('2023090417500284007BD9A49CF0BC58', $data['terminal']['terminalSafeId']);

        // Verify txnCode for non-3D
        $this->assertEquals(TxnCode::SALE, $data['txnCode']);
        $this->assertEquals('1.00', $data['version']);

        // Verify order
        $this->assertEquals('TEST-ORDER-001', $data['order']['orderId']);

        // Verify card
        $this->assertEquals('Example User', $data['card']['cardHolderName']);
        $this->assertEquals('5218076007402834', $data['card']['cardNumber']);
        $this->assertEquals('1230', $data['card']['expireDate']);
        $this->assertEquals('000', $data['card']['cvv2']);

        // Verify transaction
        $this->assertEquals('1.00', $data['transaction']['amount']);
        $this->assertEquals(949, $data['transaction']['currencyCode']);
        $this->assertEquals(0, $data['transaction']['motoInd']);
        $this->assertEquals(1, $data['transaction']['installCount']);

        // Verify customer
        $this->assertEquals('127.0.0.1', $data['customer']['ipAddress']);

        // Verify random number format (128 hex chars)
        $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $data['randomNumber']);

        // Verify requestDateTime is present
        $this->assertNotEmpty($data['requestDateTime']);
    }

    /**
     * Test 3D purchase request data structure.
     */
    public function test_purchase_3d_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest-3D.json');
        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $data = $request->getData();

        // Verify 3D-specific fields
        $this->assertEquals('3D', $data['paymentModel']);
        $this->assertEquals(TxnCode::SALE_3D, $data['txnCode']);

        // Verify merchant info at top level for 3D (form-encoded)
        $this->assertEquals('2023090417500272654BD9A49CF07574', $data['merchantSafeId']);
        $this->assertEquals('2023090417500284007BD9A49CF0BC58', $data['terminalSafeId']);

        // Verify order
        $this->assertEquals('TEST-ORDER-3D-001', $data['orderId']);

        // Verify card in secure-form field names (doc §3/§6.1)
        $this->assertEquals('Example User', $data['cardHolderName']);
        $this->assertEquals('5218076007402834', $data['creditCard']);
        $this->assertEquals('1230', $data['expiredDate']);
        $this->assertEquals('000', $data['cvv']);

        // Required secure-form fields
        $this->assertEquals('TR', $data['lang']);
        $this->assertArrayHasKey('emailAddress', $data);

        // Verify amounts
        $this->assertEquals('10.50', $data['amount']);
        $this->assertEquals('949', $data['currencyCode']);
        $this->assertEquals('1', $data['installCount']);

        // Verify URLs
        $this->assertEquals('https://example.com/payment/success', $data['okUrl']);
        $this->assertEquals('https://example.com/payment/failure', $data['failUrl']);

        // Verify hash is present
        $this->assertNotEmpty($data['hash']);

        // Verify hash is valid base64
        $decoded = base64_decode($data['hash'], true);
        $this->assertNotFalse($decoded);

        // Verify random number
        $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $data['randomNumber']);
    }

    /**
     * Test that non-3D purchase sends to API and returns non-redirect response.
     */
    public function test_purchase_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseResponseSuccess.txt');

        $response = new PurchaseResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('VPS-0000', $response->getCode());
        $this->assertEquals('SUCCESSFUL', $response->getMessage());
        $this->assertEquals('P12345', $response->getTransactionReference());
    }

    /**
     * Test API error response.
     */
    public function test_purchase_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseResponseApiError.txt');

        $response = new PurchaseResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('VPS-0012', $response->getCode());
        $this->assertEquals('INVALID TRANSACTION', $response->getMessage());
    }

    /**
     * Test 3D purchase response is a redirect.
     */
    public function test_purchase_3d_response_is_redirect()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest-3D.json');
        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        /** @var PurchaseResponse $response */
        $response = $request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('POST', $response->getRedirectMethod());

        // Test mode should point to test 3D endpoint
        $this->assertEquals(
            'https://virtualpospaymentgatewaypre.akbank.com/securepay',
            $response->getRedirectUrl()
        );

        // Redirect data should contain all form fields
        $redirectData = $response->getRedirectData();
        $this->assertEquals('3D', $redirectData['paymentModel']);
        $this->assertNotEmpty($redirectData['hash']);
    }

    /**
     * Test validation error when required fields are missing.
     */
    public function test_purchase_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest-ValidationError.json');
        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    /**
     * Test that 3D endpoint switches between test and live.
     */
    public function test_purchase_3d_endpoint_live()
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->setTestMode(false);

        $this->assertEquals(
            'https://virtualpospaymentgateway.akbank.com/securepay',
            $request->get3DEndpoint()
        );
    }

    /**
     * Test that 3D endpoint in test mode.
     */
    public function test_purchase_3d_endpoint_test()
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->setTestMode(true);

        $this->assertEquals(
            'https://virtualpospaymentgatewaypre.akbank.com/securepay',
            $request->get3DEndpoint()
        );
    }

    /**
     * Akbank test-store secret key (public, from sanalposteststore-prep.akbank.com).
     */
    private const TEST_SECRET_KEY = '3230323330393034313735303032363031353172675f357637355f3273387373745f7233725f73323333383737335f323272383774767276327672323531355f';

    /**
     * Golden vector: reproduce the exact securepay (3D_PAY) hash computed by the Akbank test store
     * for a known set of inputs. Locks the documented canonical field order + empty-optional handling.
     */
    public function test_securepay_hash_matches_bank_golden_vector()
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize([
            'merchantSafeId' => '2023090417500272654BD9A49CF07574',
            'terminalSafeId' => '2023090417500284633D137A249DBBEB',
            'secretKey' => self::TEST_SECRET_KEY,
            'paymentModel' => PaymentModel::THREE_D_PAY,
            'secure' => true,
            'transactionId' => 'd7242a5a-0fc8-41b4-a9be-2a3b50390114',
            'lang' => 'TR',
            'emailAddress' => 'test@akbank.com',
            'amount' => '11.00',
            'currency' => 'TRY',
            'installment' => 1,
            'returnUrl' => 'https://sanalposteststore-prep.akbank.com/merchantOkUrl',
            'cancelUrl' => 'https://sanalposteststore-prep.akbank.com/merchantFailUrl',
            'card' => [
                'number' => '5578293000121055',
                'expiryMonth' => '11',
                'expiryYear' => '2040',
                'cvv' => '238',
            ],
            // Fixed so the generated hash is deterministic.
            'randomNumber' => '2f0b254415d70f0daf9ed4660e69a7ca9734644313ab7545f30d1f6c91185db7e54c141a3c7595115989e9419738e54ce04f81f9a590916c8d32cc113485dc60',
            'requestDateTime' => '2026-06-02T10:42:29.217',
            'testMode' => true,
        ]);

        $data = $request->getData();

        $this->assertEquals('3D_PAY', $data['paymentModel']);
        $this->assertEquals('5578293000121055', $data['creditCard']);
        $this->assertEquals('1140', $data['expiredDate']);
        $this->assertEquals(
            'czH5cRplZom1AqIPp7YTttIaJTn943RHwe4U2s7WQRviHYENJdzwrI5ArVJblSn/F6HCc1DJ1I06kfSzmzE14Q==',
            $data['hash'],
        );
    }

    /**
     * Golden vector: reproduce the exact payhosting (3D_PAY_HOSTING) hash. No card fields are sent.
     */
    public function test_payhosting_hash_matches_bank_golden_vector()
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize([
            'merchantSafeId' => '2023090417500272654BD9A49CF07574',
            'terminalSafeId' => '2023090417500284633D137A249DBBEB',
            'secretKey' => self::TEST_SECRET_KEY,
            'paymentModel' => PaymentModel::THREE_D_PAY_HOSTING,
            'secure' => true,
            'transactionId' => 'd7242a5a-0fc8-41b4-a9be-2a3b50390114',
            'lang' => 'TR',
            'emailAddress' => 'test@akbank.com',
            'amount' => '11.00',
            'currency' => 'TRY',
            'installment' => 1,
            'returnUrl' => 'https://sanalposteststore-prep.akbank.com/merchantOkUrl',
            'cancelUrl' => 'https://sanalposteststore-prep.akbank.com/merchantFailUrl',
            'randomNumber' => 'a2e4ef22cac5758eec1777e18ea7ad0e57bd743e3f7a6bc1967ca0a773f9ef616315b6e480385942e72818b8d994675d38035bbd6957fe646c3259c587ecbb9a',
            'requestDateTime' => '2026-06-02T10:43:16.489',
            'testMode' => true,
        ]);

        $data = $request->getData();

        $this->assertEquals('3D_PAY_HOSTING', $data['paymentModel']);
        // No card data is sent in the hosted model.
        $this->assertArrayNotHasKey('creditCard', $data);
        $this->assertArrayNotHasKey('expiredDate', $data);
        $this->assertArrayNotHasKey('cvv', $data);
        $this->assertArrayNotHasKey('cardHolderName', $data);
        $this->assertEquals(
            '/a77lBaHqHJu0JydWGGEEd52Po+Ywpxyi65s2NfidK9fMNS6WueS2YaiYU+jCol56plOlQeicqlZLRUlruEAVw==',
            $data['hash'],
        );
    }

    /**
     * Hosting purchase requires no card and redirects to the /payhosting endpoint.
     */
    public function test_payhosting_endpoint_and_no_card_required()
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize([
            'merchantSafeId' => 'M',
            'terminalSafeId' => 'T',
            'secretKey' => self::TEST_SECRET_KEY,
            'paymentModel' => PaymentModel::THREE_D_PAY_HOSTING,
            'secure' => true,
            'transactionId' => 'ORDER-HOSTED-1',
            'amount' => '10.00',
            'currency' => 'TRY',
            'returnUrl' => 'https://example.com/ok',
            'cancelUrl' => 'https://example.com/fail',
            'testMode' => true,
        ]);

        // Must not throw despite no card.
        $data = $request->getData();
        $this->assertNotEmpty($data['hash']);
        $this->assertEquals(
            'https://virtualpospaymentgatewaypre.akbank.com/payhosting',
            $request->get3DEndpoint(),
        );
    }

    /**
     * The auto-submit redirect form must target _top so the bank's 3DS page escapes the iframe.
     */
    public function test_redirect_response_form_targets_top()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/PurchaseRequest-3D.json');
        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        /** @var PurchaseResponse $response */
        $response = $request->send();

        $content = $response->getRedirectResponse()->getContent();

        $this->assertStringContainsString('target="_top"', $content);
        $this->assertStringContainsString('method="post"', $content);
        $this->assertStringContainsString('document.forms[0].submit();', $content);
    }

    /**
     * Response-hash verification accepts a correctly signed callback and rejects a tampered one.
     */
    public function test_verify_response_hash()
    {
        $post = [
            'hashParams' => 'txnCode+responseCode+responseMessage',
            'txnCode' => '3000',
            'responseCode' => 'VPS-0000',
            'responseMessage' => 'SUCCESSFUL',
        ];
        $post['hash'] = Helper::hash('3000VPS-0000SUCCESSFUL', self::TEST_SECRET_KEY);

        $this->assertTrue(Helper::verifyResponseHash($post, self::TEST_SECRET_KEY));

        $tampered = $post;
        $tampered['responseCode'] = 'VPS-0001';
        $this->assertFalse(Helper::verifyResponseHash($tampered, self::TEST_SECRET_KEY));

        $this->assertFalse(Helper::verifyResponseHash(['foo' => 'bar'], self::TEST_SECRET_KEY));
    }
}

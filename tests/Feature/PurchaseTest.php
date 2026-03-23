<?php

namespace Omnipay\Akbank\Tests\Feature;

use Omnipay\Akbank\Constants\TxnCode;
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
        $this->assertEquals('1230', $data['card']['cardExpiry']);
        $this->assertEquals('000', $data['card']['cvv']);

        // Verify transaction
        $this->assertEquals('1.00', $data['transaction']['amount']);
        $this->assertEquals(949, $data['transaction']['currencyCode']);
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

        // Verify card in 3D format
        $this->assertEquals('Example User', $data['cardHolderName']);
        $this->assertEquals('5218076007402834', $data['cardNo']);
        $this->assertEquals('1230', $data['expireDate']);
        $this->assertEquals('000', $data['cvv']);

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
}

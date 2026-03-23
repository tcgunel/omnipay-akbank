<?php

namespace Omnipay\Akbank\Tests\Feature;

use Omnipay\Akbank\Constants\TxnCode;
use Omnipay\Akbank\Message\VoidRequest;
use Omnipay\Akbank\Message\VoidResponse;
use Omnipay\Akbank\Tests\TestCase;
use Omnipay\Common\Exception\InvalidRequestException;

class VoidTest extends TestCase
{
    /**
     * Test void request data structure.
     */
    public function test_void_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/VoidRequest.json');
        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new VoidRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $data = $request->getData();

        // Verify terminal block
        $this->assertEquals('2023090417500272654BD9A49CF07574', $data['terminal']['merchantSafeId']);
        $this->assertEquals('2023090417500284007BD9A49CF0BC58', $data['terminal']['terminalSafeId']);

        // Verify txnCode for cancel
        $this->assertEquals(TxnCode::CANCEL, $data['txnCode']);
        $this->assertEquals('1003', $data['txnCode']);
        $this->assertEquals('1.00', $data['version']);

        // Verify order
        $this->assertEquals('TEST-ORDER-001', $data['order']['orderId']);

        // Verify customer
        $this->assertEquals('127.0.0.1', $data['customer']['ipAddress']);

        // Verify random number
        $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $data['randomNumber']);

        // No transaction or card block for cancel
        $this->assertArrayNotHasKey('transaction', $data);
        $this->assertArrayNotHasKey('card', $data);
    }

    /**
     * Test void request validation error.
     */
    public function test_void_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/VoidRequest-ValidationError.json');
        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new VoidRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    /**
     * Test void response success.
     */
    public function test_void_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('VoidResponseSuccess.txt');

        $response = new VoidResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('VPS-0000', $response->getCode());
        $this->assertEquals('SUCCESSFUL', $response->getMessage());
    }

    /**
     * Test void response API error.
     */
    public function test_void_response_api_error()
    {
        $httpResponse = $this->getMockHttpResponse('VoidResponseApiError.txt');

        $response = new VoidResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('VPS-0051', $response->getCode());
        $this->assertEquals('INSUFFICIENT FUNDS', $response->getMessage());
    }
}

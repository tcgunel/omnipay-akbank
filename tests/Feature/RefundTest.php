<?php

namespace Omnipay\Akbank\Tests\Feature;

use Omnipay\Akbank\Constants\TxnCode;
use Omnipay\Akbank\Message\RefundRequest;
use Omnipay\Akbank\Message\RefundResponse;
use Omnipay\Akbank\Tests\TestCase;
use Omnipay\Common\Exception\InvalidRequestException;

class RefundTest extends TestCase
{
	/**
	 * Test refund request data structure.
	 */
	public function test_refund_request()
	{
		$options = file_get_contents(__DIR__ . '/../Mock/RefundRequest.json');
		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());
		$request->initialize($options);

		$data = $request->getData();

		// Verify terminal block
		$this->assertEquals('2023090417500272654BD9A49CF07574', $data['terminal']['merchantSafeId']);
		$this->assertEquals('2023090417500284007BD9A49CF0BC58', $data['terminal']['terminalSafeId']);

		// Verify txnCode for refund
		$this->assertEquals(TxnCode::REFUND, $data['txnCode']);
		$this->assertEquals('1002', $data['txnCode']);
		$this->assertEquals('1.00', $data['version']);

		// Verify order
		$this->assertEquals('TEST-ORDER-001', $data['order']['orderId']);

		// Verify transaction
		$this->assertEquals('1.00', $data['transaction']['amount']);
		$this->assertEquals(949, $data['transaction']['currencyCode']);

		// Verify customer
		$this->assertEquals('127.0.0.1', $data['customer']['ipAddress']);

		// Verify random number
		$this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $data['randomNumber']);

		// No card block for refund
		$this->assertArrayNotHasKey('card', $data);
	}

	/**
	 * Test refund request validation error.
	 */
	public function test_refund_request_validation_error()
	{
		$options = file_get_contents(__DIR__ . '/../Mock/RefundRequest-ValidationError.json');
		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());
		$request->initialize($options);

		$this->expectException(InvalidRequestException::class);

		$request->getData();
	}

	/**
	 * Test refund response success.
	 */
	public function test_refund_response_success()
	{
		$httpResponse = $this->getMockHttpResponse('RefundResponseSuccess.txt');

		$response = new RefundResponse($this->getMockRequest(), $httpResponse);

		$this->assertTrue($response->isSuccessful());
		$this->assertEquals('VPS-0000', $response->getCode());
		$this->assertEquals('SUCCESSFUL', $response->getMessage());
	}

	/**
	 * Test refund response API error.
	 */
	public function test_refund_response_api_error()
	{
		$httpResponse = $this->getMockHttpResponse('RefundResponseApiError.txt');

		$response = new RefundResponse($this->getMockRequest(), $httpResponse);

		$this->assertFalse($response->isSuccessful());
		$this->assertEquals('VPS-0012', $response->getCode());
		$this->assertEquals('INVALID TRANSACTION', $response->getMessage());
	}
}

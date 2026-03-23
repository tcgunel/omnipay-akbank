<?php

namespace Omnipay\Akbank\Tests\Feature;

use Omnipay\Akbank\Constants\TxnCode;
use Omnipay\Akbank\Message\CompletePurchaseRequest;
use Omnipay\Akbank\Message\CompletePurchaseResponse;
use Omnipay\Akbank\Tests\TestCase;
use Omnipay\Common\Exception\InvalidRequestException;

class CompletePurchaseTest extends TestCase
{
	/**
	 * Test complete purchase request data structure.
	 */
	public function test_complete_purchase_request()
	{
		$options = file_get_contents(__DIR__ . '/../Mock/CompletePurchaseRequest.json');
		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
		$request->initialize($options);

		$data = $request->getData();

		// Verify terminal block
		$this->assertEquals('2023090417500272654BD9A49CF07574', $data['terminal']['merchantSafeId']);
		$this->assertEquals('2023090417500284007BD9A49CF0BC58', $data['terminal']['terminalSafeId']);

		// Verify txnCode is SALE (the 3D completion uses txnCode=1000)
		$this->assertEquals(TxnCode::SALE, $data['txnCode']);
		$this->assertEquals('1.00', $data['version']);

		// Verify order
		$this->assertEquals('TEST-ORDER-3D-001', $data['order']['orderId']);

		// Verify transaction
		$this->assertEquals('10.50', $data['transaction']['amount']);
		$this->assertEquals(949, $data['transaction']['currencyCode']);
		$this->assertEquals(1, $data['transaction']['installCount']);

		// Verify customer
		$this->assertEquals('127.0.0.1', $data['customer']['ipAddress']);

		// Verify secureTransaction fields
		$this->assertEquals('SECURE-ID-123456', $data['secureTransaction']['secureId']);
		$this->assertEquals('02', $data['secureTransaction']['secureEcomInd']);
		$this->assertEquals('AABBCCDD112233', $data['secureTransaction']['secureData']);
		$this->assertEquals('MD-DATA-456789', $data['secureTransaction']['secureMd']);

		// Verify random number
		$this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $data['randomNumber']);
	}

	/**
	 * Test complete purchase validation error when required fields missing.
	 */
	public function test_complete_purchase_request_validation_error()
	{
		$options = file_get_contents(__DIR__ . '/../Mock/CompletePurchaseRequest-ValidationError.json');
		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
		$request->initialize($options);

		$this->expectException(InvalidRequestException::class);

		$request->getData();
	}

	/**
	 * Test complete purchase fails when mdStatus is not 1.
	 */
	public function test_complete_purchase_md_status_error()
	{
		$options = file_get_contents(__DIR__ . '/../Mock/CompletePurchaseRequest-MdStatusError.json');
		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
		$request->initialize($options);

		$this->expectException(InvalidRequestException::class);
		$this->expectExceptionMessage('3D mdStatus is not 1');

		$request->getData();
	}

	/**
	 * Test complete purchase fails when 3D auth response code is not VPS-0000.
	 */
	public function test_complete_purchase_3d_auth_error()
	{
		$options = file_get_contents(__DIR__ . '/../Mock/CompletePurchaseRequest-3DAuthError.json');
		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
		$request->initialize($options);

		$this->expectException(InvalidRequestException::class);
		$this->expectExceptionMessage('3D authentication failed');

		$request->getData();
	}

	/**
	 * Test complete purchase response success.
	 */
	public function test_complete_purchase_response_success()
	{
		$httpResponse = $this->getMockHttpResponse('CompletePurchaseResponseSuccess.txt');

		$response = new CompletePurchaseResponse($this->getMockRequest(), $httpResponse);

		$this->assertTrue($response->isSuccessful());
		$this->assertEquals('VPS-0000', $response->getCode());
		$this->assertEquals('SUCCESSFUL', $response->getMessage());
		$this->assertEquals('P99887', $response->getTransactionReference());
	}

	/**
	 * Test complete purchase response API error.
	 */
	public function test_complete_purchase_response_api_error()
	{
		$httpResponse = $this->getMockHttpResponse('CompletePurchaseResponseApiError.txt');

		$response = new CompletePurchaseResponse($this->getMockRequest(), $httpResponse);

		$this->assertFalse($response->isSuccessful());
		$this->assertEquals('VPS-0012', $response->getCode());
		$this->assertEquals('INVALID TRANSACTION', $response->getMessage());
	}
}

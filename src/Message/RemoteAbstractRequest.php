<?php

namespace Omnipay\Akbank\Message;

use Omnipay\Akbank\Helpers\Helper;
use Omnipay\Akbank\Traits\PurchaseGettersSetters;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;

abstract class RemoteAbstractRequest extends AbstractRequest
{
	use PurchaseGettersSetters;

	protected string $testEndpoint = 'https://apipre.akbank.com/api/v1/payment/virtualpos/transaction/process';

	protected string $liveEndpoint = 'https://api.akbank.com/api/v1/payment/virtualpos/transaction/process';

	protected string $version = '1.00';

	/**
	 * @throws InvalidRequestException
	 */
	protected function validateSettings(): void
	{
		$this->validate('merchantSafeId', 'terminalSafeId', 'secretKey');
	}

	protected function getEndpoint(): string
	{
		return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
	}

	protected function get_card($key)
	{
		return $this->getCard() ? $this->getCard()->$key() : null;
	}

	/**
	 * Send a JSON POST request with HMAC-SHA512 auth-hash header.
	 */
	protected function sendJsonRequest(array $data): \Psr\Http\Message\ResponseInterface
	{
		$jsonBody = json_encode($data, JSON_THROW_ON_ERROR);

		$authHash = Helper::hashJsonBody($jsonBody, $this->getSecretKey());

		return $this->httpClient->request(
			'POST',
			$this->getEndpoint(),
			[
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
				'auth-hash'    => $authHash,
			],
			$jsonBody,
		);
	}

	abstract protected function createResponse($data);
}

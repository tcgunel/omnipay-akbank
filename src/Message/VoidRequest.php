<?php

namespace Omnipay\Akbank\Message;

use Omnipay\Akbank\Constants\TxnCode;
use Omnipay\Akbank\Helpers\Helper;
use Omnipay\Akbank\Traits\PurchaseGettersSetters;
use Omnipay\Common\Exception\InvalidRequestException;

/**
 * Akbank Void (Cancel) Request - txnCode=1003
 *
 * Cancels a transaction by orderId.
 */
class VoidRequest extends RemoteAbstractRequest
{
	use PurchaseGettersSetters;

	/**
	 * @throws InvalidRequestException
	 */
	public function getData(): array
	{
		$this->validateAll();

		$randomNumber = Helper::generateRandomNumber();
		$requestDateTime = Helper::getRequestDateTime();

		return [
			'terminal'    => [
				'merchantSafeId' => $this->getMerchantSafeId(),
				'terminalSafeId' => $this->getTerminalSafeId(),
			],
			'version'          => $this->version,
			'txnCode'          => TxnCode::CANCEL,
			'requestDateTime'  => $requestDateTime,
			'randomNumber'     => $randomNumber,
			'order'            => [
				'orderId' => $this->getTransactionId(),
			],
			'customer'         => [
				'ipAddress' => $this->getClientIp() ?? '127.0.0.1',
			],
		];
	}

	/**
	 * @throws InvalidRequestException
	 */
	protected function validateAll(): void
	{
		$this->validateSettings();

		$this->validate('transactionId');
	}

	/**
	 * @param array $data
	 */
	public function sendData($data)
	{
		$httpResponse = $this->sendJsonRequest($data);

		return $this->createResponse($httpResponse);
	}

	protected function createResponse($data): VoidResponse
	{
		return $this->response = new VoidResponse($this, $data);
	}
}

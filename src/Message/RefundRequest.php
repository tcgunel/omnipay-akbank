<?php

namespace Omnipay\Akbank\Message;

use Omnipay\Akbank\Constants\TxnCode;
use Omnipay\Akbank\Helpers\Helper;
use Omnipay\Akbank\Traits\PurchaseGettersSetters;
use Omnipay\Common\Exception\InvalidRequestException;

/**
 * Akbank Refund Request - txnCode=1002
 *
 * Refunds a transaction by orderId with amount and currencyCode.
 */
class RefundRequest extends RemoteAbstractRequest
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
			'txnCode'          => TxnCode::REFUND,
			'requestDateTime'  => $requestDateTime,
			'randomNumber'     => $randomNumber,
			'order'            => [
				'orderId' => $this->getTransactionId(),
			],
			'transaction'      => [
				'amount'       => Helper::formatAmount($this->getAmount()),
				'currencyCode' => Helper::getCurrencyCode($this->getCurrency() ?? 'TRY'),
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

		$this->validate('transactionId', 'amount');
	}

	/**
	 * @param array $data
	 */
	public function sendData($data)
	{
		$httpResponse = $this->sendJsonRequest($data);

		return $this->createResponse($httpResponse);
	}

	protected function createResponse($data): RefundResponse
	{
		return $this->response = new RefundResponse($this, $data);
	}
}

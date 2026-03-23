<?php

namespace Omnipay\Akbank\Message;

use Omnipay\Akbank\Constants\ResponseCode;
use Omnipay\Akbank\Constants\TxnCode;
use Omnipay\Akbank\Helpers\Helper;
use Omnipay\Akbank\Traits\PurchaseGettersSetters;

/**
 * Akbank Complete Purchase Request
 *
 * After 3D redirect, the bank posts back responseCode, mdStatus, and secureTransaction fields.
 * If responseCode=VPS-0000 and mdStatus=1, we POST a JSON request to the API with secureTransaction data.
 */
class CompletePurchaseRequest extends RemoteAbstractRequest
{
	use PurchaseGettersSetters;

	/**
	 * @throws \Omnipay\Common\Exception\InvalidRequestException
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
			'txnCode'          => TxnCode::SALE,
			'requestDateTime'  => $requestDateTime,
			'randomNumber'     => $randomNumber,
			'order'            => [
				'orderId' => $this->getTransactionId(),
			],
			'transaction'      => [
				'amount'       => Helper::formatAmount($this->getAmount()),
				'currencyCode' => Helper::getCurrencyCode($this->getCurrency() ?? 'TRY'),
				'installCount' => (int) ($this->getInstallment() ?? 1),
			],
			'customer'         => [
				'ipAddress' => $this->getClientIp() ?? '127.0.0.1',
			],
			'secureTransaction' => [
				'secureId'     => $this->getSecureId(),
				'secureEcomInd' => $this->getSecureEcomInd(),
				'secureData'   => $this->getSecureData(),
				'secureMd'     => $this->getSecureMd(),
			],
		];
	}

	/**
	 * @throws \Omnipay\Common\Exception\InvalidRequestException
	 */
	protected function validateAll(): void
	{
		$this->validateSettings();

		$this->validate(
			'transactionId',
			'amount',
			'responseCode',
			'mdStatus',
			'secureId',
			'secureEcomInd',
			'secureData',
			'secureMd',
		);

		if ($this->getResponseCode() !== ResponseCode::SUCCESS) {
			throw new \Omnipay\Common\Exception\InvalidRequestException(
				'3D authentication failed: ' . ($this->getResponseMessage() ?? 'Unknown error')
			);
		}

		if ($this->getMdStatus() !== '1') {
			throw new \Omnipay\Common\Exception\InvalidRequestException(
				'3D mdStatus is not 1. mdStatus: ' . $this->getMdStatus()
			);
		}
	}

	/**
	 * @param array $data
	 */
	public function sendData($data)
	{
		$httpResponse = $this->sendJsonRequest($data);

		return $this->createResponse($httpResponse);
	}

	protected function createResponse($data): CompletePurchaseResponse
	{
		return $this->response = new CompletePurchaseResponse($this, $data);
	}
}

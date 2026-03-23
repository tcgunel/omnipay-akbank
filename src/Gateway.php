<?php

namespace Omnipay\Akbank;

use Omnipay\Akbank\Message\CompletePurchaseRequest;
use Omnipay\Akbank\Message\PurchaseRequest;
use Omnipay\Akbank\Message\RefundRequest;
use Omnipay\Akbank\Message\VoidRequest;
use Omnipay\Akbank\Traits\PurchaseGettersSetters;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\AbstractRequest;

/**
 * Akbank Gateway
 * (c) Tolga Can Günel
 * 2015, mobius.studio
 * http://www.github.com/tcgunel/omnipay-akbank
 * @method \Omnipay\Common\Message\NotificationInterface acceptNotification(array $options = [])
 * @method \Omnipay\Common\Message\RequestInterface authorize(array $options = [])
 * @method \Omnipay\Common\Message\RequestInterface completeAuthorize(array $options = [])
 * @method \Omnipay\Common\Message\RequestInterface capture(array $options = [])
 * @method \Omnipay\Common\Message\RequestInterface fetchTransaction(array $options = [])
 * @method \Omnipay\Common\Message\RequestInterface createCard(array $options = [])
 * @method \Omnipay\Common\Message\RequestInterface updateCard(array $options = [])
 * @method \Omnipay\Common\Message\RequestInterface deleteCard(array $options = [])
 */
class Gateway extends AbstractGateway
{
	use PurchaseGettersSetters;

	public function getName(): string
	{
		return 'Akbank';
	}

	public function getDefaultParameters()
	{
		return [
			'clientIp'        => '127.0.0.1',
			'merchantSafeId'  => '',
			'terminalSafeId'  => '',
			'secretKey'       => '',
			'installment'     => 1,
			'secure'          => false,
		];
	}

	public function purchase(array $options = []): AbstractRequest
	{
		return $this->createRequest(PurchaseRequest::class, $options);
	}

	public function completePurchase(array $options = []): AbstractRequest
	{
		return $this->createRequest(CompletePurchaseRequest::class, $options);
	}

	public function void(array $options = []): AbstractRequest
	{
		return $this->createRequest(VoidRequest::class, $options);
	}

	public function refund(array $options = []): AbstractRequest
	{
		return $this->createRequest(RefundRequest::class, $options);
	}
}

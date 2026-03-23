<?php

namespace Omnipay\Akbank\Traits;

trait PurchaseGettersSetters
{
	public function getMerchantSafeId()
	{
		return $this->getParameter('merchantSafeId');
	}

	public function setMerchantSafeId($value)
	{
		return $this->setParameter('merchantSafeId', $value);
	}

	public function getTerminalSafeId()
	{
		return $this->getParameter('terminalSafeId');
	}

	public function setTerminalSafeId($value)
	{
		return $this->setParameter('terminalSafeId', $value);
	}

	public function getSecretKey()
	{
		return $this->getParameter('secretKey');
	}

	public function setSecretKey($value)
	{
		return $this->setParameter('secretKey', $value);
	}

	public function getClientIp()
	{
		return $this->getParameter('clientIp');
	}

	public function setClientIp($value)
	{
		return $this->setParameter('clientIp', $value);
	}

	public function getInstallment()
	{
		return $this->getParameter('installment');
	}

	public function setInstallment($value)
	{
		return $this->setParameter('installment', $value);
	}

	public function getSecure()
	{
		return $this->getParameter('secure');
	}

	public function setSecure($value)
	{
		return $this->setParameter('secure', $value);
	}

	public function getOrderId()
	{
		return $this->getParameter('orderId');
	}

	public function setOrderId($value)
	{
		return $this->setParameter('orderId', $value);
	}

	public function getSecureId()
	{
		return $this->getParameter('secureId');
	}

	public function setSecureId($value)
	{
		return $this->setParameter('secureId', $value);
	}

	public function getSecureEcomInd()
	{
		return $this->getParameter('secureEcomInd');
	}

	public function setSecureEcomInd($value)
	{
		return $this->setParameter('secureEcomInd', $value);
	}

	public function getSecureData()
	{
		return $this->getParameter('secureData');
	}

	public function setSecureData($value)
	{
		return $this->setParameter('secureData', $value);
	}

	public function getSecureMd()
	{
		return $this->getParameter('secureMd');
	}

	public function setSecureMd($value)
	{
		return $this->setParameter('secureMd', $value);
	}

	public function getMdStatus()
	{
		return $this->getParameter('mdStatus');
	}

	public function setMdStatus($value)
	{
		return $this->setParameter('mdStatus', $value);
	}

	public function getResponseCode()
	{
		return $this->getParameter('responseCode');
	}

	public function setResponseCode($value)
	{
		return $this->setParameter('responseCode', $value);
	}

	public function getResponseMessage()
	{
		return $this->getParameter('responseMessage');
	}

	public function setResponseMessage($value)
	{
		return $this->setParameter('responseMessage', $value);
	}
}

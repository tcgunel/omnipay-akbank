<?php

namespace Omnipay\Akbank\Message;

use Omnipay\Akbank\Constants\TxnCode;
use Omnipay\Akbank\Helpers\Helper;
use Omnipay\Akbank\Traits\PurchaseGettersSetters;

class PurchaseRequest extends RemoteAbstractRequest
{
    use PurchaseGettersSetters;

    protected string $test3DEndpoint = 'https://virtualpospaymentgatewaypre.akbank.com/securepay';

    protected string $live3DEndpoint = 'https://virtualpospaymentgateway.akbank.com/securepay';

    /**
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     * @throws \Omnipay\Common\Exception\InvalidCreditCardException
     */
    public function getData(): array
    {
        $this->validateAll();

        if ($this->getSecure()) {
            return $this->get3DData();
        }

        return $this->getNon3DData();
    }

    /**
     * Build the JSON body for a non-3D sale (txnCode=1000).
     */
    protected function getNon3DData(): array
    {
        $randomNumber = Helper::generateRandomNumber();
        $requestDateTime = Helper::getRequestDateTime();

        return [
            'terminal' => [
                'merchantSafeId' => $this->getMerchantSafeId(),
                'terminalSafeId' => $this->getTerminalSafeId(),
            ],
            'version' => $this->version,
            'txnCode' => TxnCode::SALE,
            'requestDateTime' => $requestDateTime,
            'randomNumber' => $randomNumber,
            'order' => [
                'orderId' => $this->getTransactionId(),
            ],
            'card' => [
                'cardHolderName' => $this->get_card('getName'),
                'cardNumber' => $this->get_card('getNumber'),
                'cardExpiry' => Helper::formatExpiry(
                    $this->get_card('getExpiryMonth'),
                    $this->get_card('getExpiryYear'),
                ),
                'cvv' => $this->get_card('getCvv'),
            ],
            'transaction' => [
                'amount' => Helper::formatAmount($this->getAmount()),
                'currencyCode' => Helper::getCurrencyCode($this->getCurrency() ?? 'TRY'),
                'installCount' => (int) ($this->getInstallment() ?? 1),
            ],
            'customer' => [
                'ipAddress' => $this->getClientIp() ?? '127.0.0.1',
            ],
        ];
    }

    /**
     * Build the form data for a 3D sale (txnCode=3000).
     */
    protected function get3DData(): array
    {
        $randomNumber = Helper::generateRandomNumber();
        $requestDateTime = Helper::getRequestDateTime();
        $amount = Helper::formatAmount($this->getAmount());
        $currencyCode = (string) Helper::getCurrencyCode($this->getCurrency() ?? 'TRY');
        $installCount = (string) ((int) ($this->getInstallment() ?? 1));

        $hashData = implode('', [
            $this->getMerchantSafeId(),
            $this->getTerminalSafeId(),
            $this->getTransactionId(),
            TxnCode::SALE_3D,
            $amount,
            $currencyCode,
            $installCount,
            $this->getReturnUrl(),
            $this->getCancelUrl(),
            $randomNumber,
            $requestDateTime,
        ]);

        $hash = Helper::hash($hashData, $this->getSecretKey());

        return [
            'paymentModel' => '3D',
            'merchantSafeId' => $this->getMerchantSafeId(),
            'terminalSafeId' => $this->getTerminalSafeId(),
            'orderId' => $this->getTransactionId(),
            'txnCode' => TxnCode::SALE_3D,
            'amount' => $amount,
            'currencyCode' => $currencyCode,
            'installCount' => $installCount,
            'okUrl' => $this->getReturnUrl(),
            'failUrl' => $this->getCancelUrl(),
            'randomNumber' => $randomNumber,
            'requestDateTime' => $requestDateTime,
            'cardHolderName' => $this->get_card('getName'),
            'cardNo' => $this->get_card('getNumber'),
            'expireDate' => Helper::formatExpiry(
                $this->get_card('getExpiryMonth'),
                $this->get_card('getExpiryYear'),
            ),
            'cvv' => $this->get_card('getCvv'),
            'hash' => $hash,
        ];
    }

    /**
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     * @throws \Omnipay\Common\Exception\InvalidCreditCardException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate('card', 'amount', 'transactionId');

        $this->getCard()->validate();

        if ($this->getSecure()) {
            $this->validate('returnUrl', 'cancelUrl');
        }
    }

    /**
     * @param array $data
     */
    public function sendData($data)
    {
        if ($this->getSecure()) {
            return $this->response = new PurchaseResponse($this, $data);
        }

        $httpResponse = $this->sendJsonRequest($data);

        return $this->createResponse($httpResponse);
    }

    protected function createResponse($data): PurchaseResponse
    {
        return $this->response = new PurchaseResponse($this, $data);
    }

    public function get3DEndpoint(): string
    {
        return $this->getTestMode() ? $this->test3DEndpoint : $this->live3DEndpoint;
    }
}

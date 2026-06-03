<?php

namespace Omnipay\Akbank\Message;

use Omnipay\Akbank\Constants\PaymentModel;
use Omnipay\Akbank\Constants\TxnCode;
use Omnipay\Akbank\Helpers\Helper;
use Omnipay\Akbank\Traits\PurchaseGettersSetters;

class PurchaseRequest extends RemoteAbstractRequest
{
    use PurchaseGettersSetters;

    protected string $test3DEndpoint = 'https://virtualpospaymentgatewaypre.akbank.com/securepay';

    protected string $live3DEndpoint = 'https://virtualpospaymentgateway.akbank.com/securepay';

    protected string $testPayHostingEndpoint = 'https://virtualpospaymentgatewaypre.akbank.com/payhosting';

    protected string $livePayHostingEndpoint = 'https://virtualpospaymentgateway.akbank.com/payhosting';

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
     *
     * Card object uses the Payment API field names (doc §3 / §4.2.1): cardNumber, cvv2, expireDate.
     */
    protected function getNon3DData(): array
    {
        $randomNumber = $this->getRandomNumber() ?: Helper::generateRandomNumber();
        $requestDateTime = $this->getRequestDateTime() ?: Helper::getRequestDateTime();

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
                'expireDate' => Helper::formatExpiry(
                    (string) $this->get_card('getExpiryMonth'),
                    (string) $this->get_card('getExpiryYear'),
                ),
                'cvv2' => $this->get_card('getCvv'),
            ],
            'transaction' => [
                'amount' => Helper::formatAmount($this->getAmount()),
                'currencyCode' => Helper::getCurrencyCode($this->getCurrency() ?? 'TRY'),
                'motoInd' => 0,
                'installCount' => (int) ($this->getInstallment() ?? 1),
            ],
            'customer' => [
                'ipAddress' => $this->getClientIp() ?? '127.0.0.1',
            ],
        ];
    }

    /**
     * Build the HTML form-post data for a secure sale (txnCode=3000).
     *
     * Handles 3D / 3D_PAY (card collected merchant-side, posted to /securepay) and
     * 3D_PAY_HOSTING (no card data, bank-hosted page, posted to /payhosting).
     *
     * The hash is built over the documented canonical field order with empty strings for
     * unset optional fields (doc §6.1.1 / §6.3.1; verified against the Akbank test store).
     * PHP preserves insertion order, so array_values() yields the exact concatenation order.
     */
    protected function get3DData(): array
    {
        $randomNumber = $this->getRandomNumber() ?: Helper::generateRandomNumber();
        $requestDateTime = $this->getRequestDateTime() ?: Helper::getRequestDateTime();

        $isHosting = $this->isHosting();

        $fields = [
            'paymentModel' => $this->getPaymentModel() ?: PaymentModel::THREE_D,
            'txnCode' => TxnCode::SALE_3D,
            'merchantSafeId' => (string) $this->getMerchantSafeId(),
            'terminalSafeId' => (string) $this->getTerminalSafeId(),
            'orderId' => (string) $this->getTransactionId(),
            'lang' => (string) ($this->getLang() ?: 'TR'),
            'amount' => Helper::formatAmount($this->getAmount()),
            'ccbRewardAmount' => '0.00',
            'pcbRewardAmount' => '0.00',
            'xcbRewardAmount' => '0.00',
            'currencyCode' => (string) Helper::getCurrencyCode($this->getCurrency() ?? 'TRY'),
            'installCount' => (string) ((int) ($this->getInstallment() ?? 1)),
            'okUrl' => (string) $this->getReturnUrl(),
            'failUrl' => (string) $this->getCancelUrl(),
            'emailAddress' => (string) ($this->getEmailAddress() ?? $this->get_card('getEmail') ?? ''),
            'mobilePhone' => '',
            'homePhone' => '',
            'workPhone' => '',
            'subMerchantId' => (string) ($this->getSubMerchantId() ?? ''),
        ];

        // 3D Pay Hosting collects the card on the bank's own page: no card fields are sent or hashed.
        if (! $isHosting) {
            $fields['creditCard'] = (string) $this->get_card('getNumber');
            $fields['expiredDate'] = Helper::formatExpiry(
                (string) $this->get_card('getExpiryMonth'),
                (string) $this->get_card('getExpiryYear'),
            );
            $fields['cvv'] = (string) $this->get_card('getCvv');
            $fields['cardHolderName'] = (string) ($this->get_card('getName') ?? '');
        }

        $fields['randomNumber'] = $randomNumber;
        $fields['requestDateTime'] = $requestDateTime;
        $fields['b2bIdentityNumber'] = '';
        $fields['merchantData'] = '';
        $fields['merchantBranchNo'] = '';
        $fields['mobileEci'] = '';
        $fields['walletProgramData'] = '';
        $fields['mobileAssignedId'] = '';
        $fields['mobileDeviceType'] = '';

        $fields['hash'] = Helper::hash(implode('', array_values($fields)), $this->getSecretKey());

        return $fields;
    }

    /**
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     * @throws \Omnipay\Common\Exception\InvalidCreditCardException
     */
    protected function validateAll(): void
    {
        $this->validateSettings();

        $this->validate('amount', 'transactionId');

        // Card is required everywhere except the bank-hosted secure flow.
        if (! ($this->getSecure() && $this->isHosting())) {
            $this->validate('card');
            $this->getCard()->validate();
        }

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

    public function isHosting(): bool
    {
        return PaymentModel::isHosted($this->getPaymentModel());
    }

    public function get3DEndpoint(): string
    {
        if ($this->isHosting()) {
            return $this->getTestMode() ? $this->testPayHostingEndpoint : $this->livePayHostingEndpoint;
        }

        return $this->getTestMode() ? $this->test3DEndpoint : $this->live3DEndpoint;
    }
}

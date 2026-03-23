<?php

namespace Omnipay\Akbank\Tests\Feature;

use Omnipay\Akbank\Message\CompletePurchaseRequest;
use Omnipay\Akbank\Message\PurchaseRequest;
use Omnipay\Akbank\Message\RefundRequest;
use Omnipay\Akbank\Message\VoidRequest;
use Omnipay\Akbank\Tests\TestCase;

class GatewayTest extends TestCase
{
    public function test_gateway_name()
    {
        $this->assertEquals('Akbank', $this->gateway->getName());
    }

    public function test_gateway_default_parameters()
    {
        $defaults = $this->gateway->getDefaultParameters();

        $this->assertArrayHasKey('clientIp', $defaults);
        $this->assertArrayHasKey('merchantSafeId', $defaults);
        $this->assertArrayHasKey('terminalSafeId', $defaults);
        $this->assertArrayHasKey('secretKey', $defaults);
        $this->assertArrayHasKey('installment', $defaults);
        $this->assertArrayHasKey('secure', $defaults);
    }

    public function test_gateway_purchase_returns_purchase_request()
    {
        $request = $this->gateway->purchase([]);

        $this->assertInstanceOf(PurchaseRequest::class, $request);
    }

    public function test_gateway_complete_purchase_returns_request()
    {
        $request = $this->gateway->completePurchase([]);

        $this->assertInstanceOf(CompletePurchaseRequest::class, $request);
    }

    public function test_gateway_void_returns_void_request()
    {
        $request = $this->gateway->void([]);

        $this->assertInstanceOf(VoidRequest::class, $request);
    }

    public function test_gateway_refund_returns_refund_request()
    {
        $request = $this->gateway->refund([]);

        $this->assertInstanceOf(RefundRequest::class, $request);
    }

    public function test_gateway_setters_getters()
    {
        $this->gateway->setMerchantSafeId('test-merchant-safe-id');
        $this->assertEquals('test-merchant-safe-id', $this->gateway->getMerchantSafeId());

        $this->gateway->setTerminalSafeId('test-terminal-safe-id');
        $this->assertEquals('test-terminal-safe-id', $this->gateway->getTerminalSafeId());

        $this->gateway->setSecretKey('test-secret-key');
        $this->assertEquals('test-secret-key', $this->gateway->getSecretKey());

        $this->gateway->setClientIp('192.168.1.1');
        $this->assertEquals('192.168.1.1', $this->gateway->getClientIp());

        $this->gateway->setInstallment(3);
        $this->assertEquals(3, $this->gateway->getInstallment());

        $this->gateway->setSecure(true);
        $this->assertTrue($this->gateway->getSecure());
    }
}

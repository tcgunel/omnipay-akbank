# Omnipay: Akbank

**Akbank Virtual POS driver for the Omnipay PHP payment processing library**

[![Latest Stable Version](https://poser.pugx.org/tcgunel/omnipay-akbank/v)](https://packagist.org/packages/tcgunel/omnipay-akbank)
[![Total Downloads](https://poser.pugx.org/tcgunel/omnipay-akbank/downloads)](https://packagist.org/packages/tcgunel/omnipay-akbank)
[![License](https://poser.pugx.org/tcgunel/omnipay-akbank/license)](https://packagist.org/packages/tcgunel/omnipay-akbank)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP. This package implements Akbank Virtual POS support for Omnipay.

Akbank uses a modern REST JSON API with HMAC-SHA512 request signing.

## Installation

```bash
composer require tcgunel/omnipay-akbank
```

## Requirements

- PHP >= 8.0
- ext-json

## Usage

### Gateway Initialization

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('Akbank');

$gateway->setMerchantSafeId('your-merchant-safe-id');
$gateway->setTerminalSafeId('your-terminal-safe-id');
$gateway->setSecretKey('your-secret-key');
$gateway->setTestMode(true); // Use test endpoints
```

### Non-3D Payment (Direct Sale)

```php
$response = $gateway->purchase([
    'amount'        => '100.00',
    'currency'      => 'TRY',
    'transactionId' => 'ORDER-001',
    'clientIp'      => '127.0.0.1',
    'installment'   => 1,
    'secure'        => false,
    'card'          => [
        'firstName'   => 'John',
        'lastName'    => 'Doe',
        'number'      => '5218076007402834',
        'expiryMonth' => '12',
        'expiryYear'  => '2030',
        'cvv'         => '000',
    ],
])->send();

if ($response->isSuccessful()) {
    echo 'Payment successful! Auth Code: ' . $response->getTransactionReference();
} else {
    echo 'Payment failed: ' . $response->getMessage();
    echo ' Code: ' . $response->getCode();
}
```

### 3D Secure Payment

```php
$response = $gateway->purchase([
    'amount'        => '100.00',
    'currency'      => 'TRY',
    'transactionId' => 'ORDER-001',
    'clientIp'      => '127.0.0.1',
    'installment'   => 1,
    'secure'        => true,
    'returnUrl'     => 'https://example.com/payment/success',
    'cancelUrl'     => 'https://example.com/payment/failure',
    'card'          => [
        'firstName'   => 'John',
        'lastName'    => 'Doe',
        'number'      => '5218076007402834',
        'expiryMonth' => '12',
        'expiryYear'  => '2030',
        'cvv'         => '000',
    ],
])->send();

if ($response->isRedirect()) {
    $response->redirect(); // Redirects to bank 3D page
}
```

### Complete 3D Purchase (Callback Handler)

After the bank redirects back to your `returnUrl`, complete the purchase:

```php
$response = $gateway->completePurchase([
    'transactionId' => $_POST['orderId'],
    'amount'        => '100.00',
    'currency'      => 'TRY',
    'clientIp'      => $_SERVER['REMOTE_ADDR'],
    'installment'   => 1,
    'responseCode'  => $_POST['responseCode'],
    'mdStatus'      => $_POST['mdStatus'],
    'secureId'      => $_POST['secureId'],
    'secureEcomInd' => $_POST['secureEcomInd'],
    'secureData'    => $_POST['secureData'],
    'secureMd'      => $_POST['secureMd'],
])->send();

if ($response->isSuccessful()) {
    echo 'Payment successful! Auth Code: ' . $response->getTransactionReference();
} else {
    echo 'Payment failed: ' . $response->getMessage();
}
```

### Cancel (Void)

```php
$response = $gateway->void([
    'transactionId' => 'ORDER-001',
    'clientIp'      => '127.0.0.1',
])->send();

if ($response->isSuccessful()) {
    echo 'Transaction cancelled successfully.';
} else {
    echo 'Cancel failed: ' . $response->getMessage();
}
```

### Refund

```php
$response = $gateway->refund([
    'transactionId' => 'ORDER-001',
    'amount'        => '50.00',
    'currency'      => 'TRY',
    'clientIp'      => '127.0.0.1',
])->send();

if ($response->isSuccessful()) {
    echo 'Refund successful.';
} else {
    echo 'Refund failed: ' . $response->getMessage();
}
```

## API Details

### Transaction Codes
| Code | Operation |
|------|-----------|
| 1000 | Sale (Non-3D) |
| 3000 | Sale (3D Secure) |
| 1002 | Refund |
| 1003 | Cancel (Void) |

### Endpoints
| Environment | API URL |
|-------------|---------|
| Test API | `https://apipre.akbank.com/api/v1/payment/virtualpos/transaction/process` |
| Live API | `https://api.akbank.com/api/v1/payment/virtualpos/transaction/process` |
| Test 3D | `https://virtualpospaymentgatewaypre.akbank.com/securepay` |
| Live 3D | `https://virtualpospaymentgateway.akbank.com/securepay` |

### Authentication
- Each API request includes HMAC-SHA512 hash of the JSON body as the `auth-hash` header
- 3D requests include HMAC-SHA512 hash of concatenated form fields
- All requests include a 128-character random hex number and ISO datetime

### Currency Codes
| Currency | Code |
|----------|------|
| TRY | 949 |
| USD | 840 |
| EUR | 978 |
| GBP | 826 |
| JPY | 392 |

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

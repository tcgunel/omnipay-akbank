<?php

namespace Omnipay\Akbank\Constants;

/**
 * Akbank secure payment models (doc §6).
 *
 * - THREE_D            : two-step. Merchant collects card, 3D auth on /securepay,
 *                        then a separate provizyon JSON call (completePurchase).
 * - THREE_D_PAY        : one-step. Merchant collects card, 3D auth + provizyon on /securepay.
 * - THREE_D_PAY_HOSTING: one-step. Bank collects card on its own hosted page (/payhosting);
 *                        no card data is sent by the merchant and no completePurchase is needed.
 */
class PaymentModel
{
    public const THREE_D = '3D';

    public const THREE_D_PAY = '3D_PAY';

    public const THREE_D_PAY_HOSTING = '3D_PAY_HOSTING';

    /**
     * Models whose card form is hosted by the bank (posted to /payhosting, no card fields).
     */
    public const HOSTED = [
        self::THREE_D_PAY_HOSTING,
    ];

    public static function isHosted(?string $model): bool
    {
        return in_array($model, self::HOSTED, true);
    }
}

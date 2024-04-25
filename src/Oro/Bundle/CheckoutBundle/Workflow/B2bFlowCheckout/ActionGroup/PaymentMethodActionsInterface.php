<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

interface PaymentMethodActionsInterface
{
    public function validate(
        Checkout $checkout,
        string $successUrl,
        string $failureUrl,
        ?string $additionalData,
        ?bool $saveForLaterUse
    ): array;

    public function isPaymentMethodSupportsValidate(Checkout $checkout): bool;
}
